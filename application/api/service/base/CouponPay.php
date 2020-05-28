<?php
/**
 * Created by 七月.
 * Author: 七月
 * 微信公号：小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/2/26
 * Time: 16:02
 */

namespace app\api\service\base;


use app\api\model\Order as OrderModel;
use app\api\model\ThirdApp as ThirdappModel;
use app\api\model\User as UserModel;
use app\api\model\UserCoupon as UserCouponModel;
use app\api\model\FlowLog;

use app\api\service\base\WxPay;
use app\api\service\base\CommonService as CommonService;
use app\api\service\beforeModel\Common as BeforeModel;
use app\api\service\func\FlowLog as FlowLogService;
use app\api\validate\CommonValidate as CommonValidate;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

use think\Exception;
use think\Loader;
use think\Log;
use think\Db;
use think\Cache;


class CouponPay
{

	function __construct(){

	}

	public static function couponPay($data,$inner=false)
	{
		if(!$inner){
			(new CommonValidate())->goCheck('one',$data);
			checkTokenAndScope($data,config('scope.two')); 
		};

		$couponInfo = BeforeModel::CommonGet('UserCoupon',$data);
		if(count($couponInfo['data'])>0){
			$couponInfo = $couponInfo['data'][0];
			self::checkCouponStock($couponInfo);

			if(!$couponInfo['pay_no']&&!isset($data['pay_no'])){
				$data['pay_no'] = makePayNo();
			}else if($couponInfo['pay_no']){
				$data['pay_no'] = $couponInfo['pay_no'];
			}
			$modelData = [];
			$modelData['searchItem'] = [
				'user_no'=>$couponInfo['user_no']
			];
			$userInfo =  BeforeModel::CommonGet('User',$modelData);
			if(count($userInfo['data'])==0){
				throw new ErrorMessage([
					'msg' => 'userInfo未创建',
				]);
			};
			$userInfo = $userInfo['data'][0];
			$couponInfo = self::checkParamValid($data,$couponInfo,$userInfo);
		}else{
			throw new ErrorMessage([
				'msg' => '优惠券不存在',
			]);
		}

		if(!isset($data['wxPayStatus'])){
			$data['wxPayStatus'] = 0;
		};
		if(isset($data['wxPay'])&&isset($data['wxPayStatus'])&&$data['wxPayStatus']==0){
			//记录订单的全部信息，回调时执行其它支付方式
			$logData['pay_info'] = $data;
			$logData['pay_info']['iscoupon'] = "true";
			return WxPay::pay($userInfo,$data['pay_no'],$data['wxPay']['price'],$logData);
		};

		Db::startTrans();
		try{
			if(isset($data['balance'])){
				self::balancePay($userInfo,$couponInfo,$data['balance'],$data);
			};
			if(isset($data['score'])){
				self::scorePay($userInfo,$couponInfo,$data['score'],$data);
			};
			Db::commit();
		}catch (Exception $ex){
			Db::rollback();
			throw $ex;
		};

		throw new SuccessMessage([
			'msg' => '支付成功',
		]);
	}

	public static function checkCouponStock($couponInfo)
	{
		$modelData = [];
		$modelData['searchItem'] = ['coupon_no'=>$couponInfo['coupon_no']];
		$Info = BeforeModel::CommonGet('Coupon',$modelData);
		if(!count($Info['data'])>0){
			throw new ErrorMessage([
				'msg' => '优惠券关联信息有误',
			]);
		};
		$Info = $Info['data'][0];
		if($Info['stock']<1){
			throw new ErrorMessage([
				'msg' => '库存不足',
				'info'=>$Info
			]);
		};
	}


	public static function balancePay($userInfo,$couponInfo,$balance,$data)
	{
		if($balance['price']>$userInfo['info']['balance']){
			throw new ErrorMessage([
				'msg'=>'余额不足'
			]);
		};

		/*是否有子订单*/
		$modelData = [];
		$modelData['searchItem']['parent_no'] = $couponInfo['id'];
		$modelData['searchItem']['pay_status'] = 0;
		$childs = BeforeModel::CommonGet('UserCoupon',$modelData);
		$pay = $balance['price'];

		if(count($childs['data'])>0){

			foreach($childs['data'] as $coupon_key => $coupon_value){
				$modelData = [];
				$modelData['searchItem']['relation_id'] = $coupon_value['id'];
				$modelData['searchItem']['relation_table'] = 'coupon';
				$flows = BeforeModel::CommonGet('FlowLog',$modelData);
				$flowPrice = 0;
				if(count($flows['data'])>0){
					foreach ($flows['data'] as $flow_key => $flow_value) {
						$flowPrice += abs($flow_value['count']);
					};
				};
				if($pay>0){
					if($flowPrice>0){
						if($pay>=($coupon_value['price']-$flowPrice)){
							$count = $coupon_value['price']-$flowPrice;
							$pay -= $coupon_value['price']-$flowPrice;
						}else{
							$count = $pay;
							$pay = 0;
						}
					}else{
						if($pay>=$coupon_value['price']){
							$count = $coupon_value['price'];
							$pay -= $coupon_value['price'];
						}else{
							$count = $pay;
							$pay = 0;
						}
					}
				};
				$modelData = [];
				$modelData['data'] = array(
					'type' => 2,
					'account' => 1,
					'count'=>-$count,
					'relation_id'=>$coupon_value['id'],
					'parent_no'=>isset($coupon_value['parent_no'])?$coupon_value['parent_no']:'',
					'pay_no'=>$data['pay_no'],
					'trade_info'=>'余额支付',
					'relation_table'=>'coupon',
					'thirdapp_id'=>$userInfo['thirdapp_id'],
					'user_no'=>$userInfo['user_no'],
				);
		
				$modelData['FuncName'] = 'add';
				$res = BeforeModel::CommonSave('FlowLog',$modelData);
				
				if(!$res>0){
					throw new ErrorMessage([
						'msg'=>'余额支付失败'
					]);
				};
				
				$modelData = [];
				$modelData['searchItem']['id'] = $res;
				FlowLogService::checkIsPayAll($modelData);
			};
		
		}else{
			
			$modelData = [];
			$modelData['data'] = array(
				'type' => 2,
				'account' => 1,
				'count'=>-$pay,
				'relation_id'=>$couponInfo['id'],
				'parent_no'=>isset($couponInfo['parent_no'])?$couponInfo['parent_no']:'',
				'pay_no'=>$data['pay_no'],
				'trade_info'=>'余额支付',
				'relation_table'=>'coupon',
				'thirdapp_id'=>$userInfo['thirdapp_id'],
				'user_no'=>$userInfo['user_no'],
			);
			
			$modelData['FuncName'] = 'add';
			$res = BeforeModel::CommonSave('FlowLog',$modelData);
			
			if(!$res>0){
				throw new ErrorMessage([
					'msg'=>'余额支付失败'
				]);
			};
		
			$modelData = [];
			$modelData['searchItem']['id'] = $res;
			FlowLogService::checkIsPayAll($modelData);
			
		};

	}


	public static function scorePay($userInfo,$couponInfo,$score,$data)
	{
		if($score['price']>$userInfo['info']['score']){
			throw new ErrorMessage([
				'msg'=>'积分不足'
			]);
		};
		
		/*是否有子订单*/
		$modelData = [];
		$modelData['searchItem']['parent_no'] = $couponInfo['id'];
		$modelData['searchItem']['pay_status'] = 0;
		$childs = BeforeModel::CommonGet('UserCoupon',$modelData);
		$pay = $score['price'];
		
		if(count($childs['data'])>0){

			foreach($childs['data'] as $coupon_key => $coupon_value){
				$modelData = [];
				$modelData['searchItem']['relation_id'] = $coupon_value['id'];
				$modelData['searchItem']['relation_table'] = 'coupon';
				$flows = BeforeModel::CommonGet('FlowLog',$modelData);
				$flowPrice = 0;
				if(count($flows['data'])>0){
					foreach ($flows['data'] as $flow_key => $flow_value) {
						$flowPrice += abs($flow_value['count']);
					};
				};
				if($pay>0){
					if($flowPrice>0){
						if($pay>=($coupon_value['price']-$flowPrice)){
							$count = $coupon_value['price']-$flowPrice;
							$pay -= $coupon_value['price']-$flowPrice;
						}else{
							$count = $pay;
							$pay = 0;
						}
					}else{
						if($pay>=$coupon_value['price']){
							$count = $coupon_value['price'];
							$pay -= $coupon_value['price'];
						}else{
							$count = $pay;
							$pay = 0;
						}
					}
				};
				$modelData = [];
				$modelData['data'] = array(
					'type' => 3,
					'account' => 1,
					'count'=>-$count,
					'relation_id'=>$coupon_value['id'],
					'parent_no'=>isset($coupon_value['parent_no'])?$coupon_value['parent_no']:'',
					'pay_no'=>$data['pay_no'],
					'trade_info'=>'积分支付',
					'relation_table'=>'coupon',
					'thirdapp_id'=>$userInfo['thirdapp_id'],
					'user_no'=>$userInfo['user_no'],
				);
		
				$modelData['FuncName'] = 'add';
				$res = BeforeModel::CommonSave('FlowLog',$modelData);
				
				if(!$res>0){
					throw new ErrorMessage([
						'msg'=>'积分支付失败'
					]);
				};
				
				$modelData = [];
				$modelData['searchItem']['id'] = $res;
				FlowLogService::checkIsPayAll($modelData);
			};
		
		}else{
			
			$modelData = [];
			$modelData['data'] = array(
				'type' => 3,
				'account' => 1,
				'count'=>-$pay,
				'relation_id'=>$couponInfo['id'],
				'parent_no'=>isset($couponInfo['parent_no'])?$couponInfo['parent_no']:'',
				'pay_no'=>$data['pay_no'],
				'trade_info'=>'积分支付',
				'relation_table'=>'coupon',
				'thirdapp_id'=>$userInfo['thirdapp_id'],
				'user_no'=>$userInfo['user_no'],
			);
			
			$modelData['FuncName'] = 'add';
			$res = BeforeModel::CommonSave('FlowLog',$modelData);
			
			if(!$res>0){
				throw new ErrorMessage([
					'msg'=>'积分支付失败'
				]);
			};
		
			$modelData = [];
			$modelData['searchItem']['id'] = $res;
			FlowLogService::checkIsPayAll($modelData);
			
		};
	}



	public static function checkParamValid($data,$couponInfo,$userInfo)
	{
		if($couponInfo['pay_status'] == '1'){
			throw new ErrorMessage([
				'msg' => '优惠券已支付',
			]);
		};
		if(isset($data['balance'])){
			if($data['balance']['price']>(float)$userInfo['info']['balance']){
				throw new ErrorMessage([
					'msg' => '余额不足',
				]);
			};
		};
		if(isset($data['score'])){
			if(($data['score']['price']/(int)$userInfo['info']['score_ratio'])>(float)$userInfo['info']['score']){
				throw new ErrorMessage([
					'msg' => '积分不足',
				]);
			};
		};

		$modelData = [];
		$modelData['searchItem']['id'] = $couponInfo['id'];
		$modelData['data'] = [];
		if(isset($data['data'])){
			$modelData['data'] = $data['data'];
		}; 
		if(isset($data['pay_no'])){
			$modelData['data']['pay_no'] = $data['pay_no'];
			$couponInfo['pay_no'] = $modelData['data']['pay_no'];
		};   
		$modelData['FuncName'] = 'update';
		if($modelData['data']){
			$res = BeforeModel::CommonSave('UserCoupon',$modelData);
			if(!$res>0){
				throw new ErrorMessage([
					'msg'=>'更新CouponPay信息失败'
				]);
			};
		};
		return $couponInfo;
	}

}