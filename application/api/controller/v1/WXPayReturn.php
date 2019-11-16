<?php
/**
 * Created by wjm.
 * Author: wjm
 * Date: 2018/7/13
 * Time: 16:41
 */

namespace app\api\controller\v1;

use think\Db;
use think\Controller;

use app\api\model\FlowLog;
use app\api\model\PayLog;

use app\api\service\base\Pay as PayService;
use app\api\service\base\CouponPay as CouponPayService;
use app\api\service\func\FlowLog as FlowLogService;
use app\api\service\beforeModel\Common as BeforeModel;
use app\api\service\project\Solely as SolelyService;

use think\Request as Request;

use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class WXPayReturn extends Controller
{
	//支付回调
	public function receiveNotify(){

		// $data = Request::instance()->param();
		$xmlData = file_get_contents('php://input');
		$data = xml2array($xmlData);
		//开始支付回调逻辑....
		if($data['RESULT_CODE']=='SUCCESS'){

			$orderId = $data['OUT_TRADE_NO'];
			$payLog = resDeal([PayLog::get(['pay_no'=>$orderId])])[0];
			if($payLog&&$payLog['transaction_id']==$data['TRANSACTION_ID']){
				echo 'SUCCESS';
				return;
			};
			
			$modelData = [];
			$modelData['searchItem'] = [
				'pay_no'=>$orderId
			];

			$modelData['data'] = array(
				'title'=>'微信支付回调成功',
				'transaction_id'=>$data['TRANSACTION_ID'],
				'content'=>$data,
				'pay_no'=>$orderId,
				'update_time'=>time(),
			);
			$modelData['FuncName'] = 'update';
			$saveLog = BeforeModel::CommonSave('PayLog',$modelData);

			if($payLog['behavior']==1){
				echo 'SUCCESS';
				return;
			};
			$TOTAL_FEE = $data['TOTAL_FEE']/100;

			if (isset($payLog['pay_info'])&&isset($payLog['pay_info']['iscoupon'])&&!empty($payLog['pay_info']['iscoupon'])) {
				$modelData = [];
				$modelData['searchItem']['pay_no'] = $orderId;
				$couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
				$this->dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog);
				echo 'SUCCESS';
				return;
			}

			$modelData = [];
			$modelData['searchItem']['order_no'] = $payLog['order_no'];
			$orderInfo = BeforeModel::CommonGet('Order',$modelData);
			if(count($orderInfo['data'])>0){
				$orderInfo = $orderInfo['data'][0];
				$this->dealOrder($data,$orderInfo,$TOTAL_FEE,$payLog);
			}
			return;

		}else{

			//记录微信支付回调日志
			$modelData = [];
			$modelData['FuncName'] = 'add';
			$modelData['data'] = array(
				'title'=>'微信支付',
				'result'=>$data['RESULT_CODE'],
				'content'=>$data['RETURN_MSG'],
				'pay_no'=>$data['OUT_TRADE_NO'],
				'create_time'=>time(),
				'type'=>2,
			);
			$saveLog = BeforeModel::CommonSave('PayLog',$modelData);
		}
	}


	public function dealOrder($data,$orderInfo,$TOTAL_FEE,$payLog)
	{
		
		$modelData = [];
		$modelData['searchItem']['parent_no'] = $orderInfo['order_no'];
		$modelData['searchItem']['pay_status'] = 0;
		$childs = BeforeModel::CommonGet('Order',$modelData);
		
		if(count($childs['data'])>0){
			
			$wxpay = $TOTAL_FEE;
			foreach($childs['data'] as $order_key => $order_value){
				$modelData = [];
				$modelData['searchItem']['order_no'] = $order_value['order_no'];
				$flows = BeforeModel::CommonGet('FlowLog',$modelData);
				$flowPrice = 0;
				if(count($flows['data'])>0){
					foreach ($flows['data'] as $flow_key => $flow_value) {
						$flowPrice += abs($flow_value['count']);
					};
				};
				if($wxpay>0){
					if($flowPrice>0){
						if($wxpay>=($order_value['price']-$flowPrice)){
							$count = $order_value['price']-$flowPrice;
							$wxpay -= $order_value['price']-$flowPrice;
						}else{
							$count = $wxpay;
							$wxpay = 0;
						}
					}else{
						if($wxpay>=$order_value['price']){
							$count = $order_value['price'];
							$wxpay -= $order_value['price'];
						}else{
							$count = $wxpay;
							$wxpay = 0;
						}
					}
				};
				$modelData = [];
				$modelData['data'] = array(
					'type' => 1,
					'account' => 1,
					'count'=>-$count,
					'order_no'=>isset($order_value['order_no'])?$order_value['order_no']:'',
					'parent_no'=>isset($order_value['parent_no'])?$order_value['parent_no']:'',
					'pay_no'=>$payLog['pay_no'],
					'trade_info'=>'微信支付',
					'thirdapp_id'=>$payLog['thirdapp_id'],
					'user_no'=>$payLog['user_no'],
					'relation_table'=>'order',
					'payInfo'=>[
						'appid'=>$data['APPID'],
						'mch_id'=>$data['MCH_ID'],
						'transaction_id'=>$data['TRANSACTION_ID'],
						'out_trade_no'=>$data['OUT_TRADE_NO'],
					]
				);

				$modelData['FuncName'] = 'add';
				$res = BeforeModel::CommonSave('FlowLog',$modelData);
				
				$modelData = [];
				$modelData['searchItem']['id'] = $res;
				FlowLogService::checkIsPayAll($modelData);
			};

		}else{
			
			$modelData = [];
			$modelData['data'] = array(
				'type' => 1,
				'account' => 1,
				'count'=>-$TOTAL_FEE,
				'order_no'=>isset($orderInfo['order_no'])?$orderInfo['order_no']:'',
				'parent_no'=>isset($orderInfo['parent_no'])?$orderInfo['parent_no']:'',
				'pay_no'=>$payLog['pay_no'],
				'trade_info'=>'微信支付',
				'thirdapp_id'=>$payLog['thirdapp_id'],
				'user_no'=>$payLog['user_no'],
				'relation_table'=>'order',
				'payInfo'=>[
					'appid'=>$data['APPID'],
					'mch_id'=>$data['MCH_ID'],
					'transaction_id'=>$data['TRANSACTION_ID'],
					'out_trade_no'=>$data['OUT_TRADE_NO'],
				]
			);
			
			$modelData['FuncName'] = 'add';
			$res = BeforeModel::CommonSave('FlowLog',$modelData);

			$modelData = [];
			$modelData['searchItem']['id'] = $res;
			FlowLogService::checkIsPayAll($modelData);
			
		};
		
		if(!empty($payLog['saveFunction'])){
			SolelyService::saveFunction($payLog['saveFunction']);
		};

		$modelData = [];
		$modelData = $payLog;
		//取出第一次调取支付时记录的信息
		$modelData = array_merge($modelData,$payLog['pay_info']);
		$modelData['wxPayStatus'] = 1;
		$modelData['searchItem'] = [
			'order_no'=>$payLog['order_no']
		];
		unset($modelData['pay_info']);

		PayService::pay($modelData,true);
	}


	public function dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog)
	{

		$modelData = [];
		$modelData['searchItem']['parent_no'] = $couponInfo['id'];
		$modelData['searchItem']['pay_status'] = 0;
		$childs = BeforeModel::CommonGet('Order',$modelData);
		
		if(count($childs['data'])>0){
			
			$wxpay = $TOTAL_FEE;
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
				if($wxpay>0){
					if($flowPrice>0){
						if($wxpay>=($coupon_value['price']-$flowPrice)){
							$count = $coupon_value['price']-$flowPrice;
							$wxpay -= $coupon_value['price']-$flowPrice;
						}else{
							$count = $wxpay;
							$wxpay = 0;
						}
					}else{
						if($wxpay>=$coupon_value['price']){
							$count = $coupon_value['price'];
							$wxpay -= $coupon_value['price'];
						}else{
							$count = $wxpay;
							$wxpay = 0;
						}
					}
				};
				$modelData = [];
				$modelData['data'] = array(
					'type' => 1,
					'count'=>-$count,
					'relation_id'=>$coupon_value['id'],
					'parent_no'=>isset($coupon_value['parent_no'])?$coupon_value['parent_no']:'',
					'pay_no'=>$payLog['pay_no'],
					'trade_info'=>'微信支付',
					'thirdapp_id'=>$payLog['thirdapp_id'],
					'user_no'=>$payLog['user_no'],
					'relation_table'=>'coupon',
					'payInfo'=>[
						'appid'=>$data['APPID'],
						'mch_id'=>$data['MCH_ID'],
						'transaction_id'=>$data['TRANSACTION_ID'],
						'out_trade_no'=>$data['OUT_TRADE_NO'],
					]
				);

				$modelData['FuncName'] = 'add';
				$res = BeforeModel::CommonSave('FlowLog',$modelData);
				
				$modelData = [];
				$modelData['searchItem']['id'] = $res;
				FlowLogService::checkIsPayAll($modelData);
			};

		}else{
			
			$modelData = [];
			$modelData['data'] = array(
				'type' => 1,
				'count'=>-$TOTAL_FEE,
				'relation_id'=>$coupon_value['id'],
				'parent_no'=>isset($orderInfo['parent_no'])?$orderInfo['parent_no']:'',
				'pay_no'=>$payLog['pay_no'],
				'trade_info'=>'微信支付',
				'thirdapp_id'=>$payLog['thirdapp_id'],
				'user_no'=>$payLog['user_no'],
				'relation_table'=>'coupon',
				'payInfo'=>[
					'appid'=>$data['APPID'],
					'mch_id'=>$data['MCH_ID'],
					'transaction_id'=>$data['TRANSACTION_ID'],
					'out_trade_no'=>$data['OUT_TRADE_NO'],
				]
			);
			
			$modelData['FuncName'] = 'add';
			$res = BeforeModel::CommonSave('FlowLog',$modelData);

			$modelData = [];
			$modelData['searchItem']['id'] = $res;
			FlowLogService::checkIsPayAll($modelData);
			
		};

		$modelData = $payLog;
		//取出第一次调取支付时记录的信息
		$modelData = array_merge($modelData,$payLog['pay_info']);
		$modelData['wxPayStatus'] = 1;
		$modelData['searchItem'] = [
			'pay_no'=>$payLog['pay_no']
		];
		unset($modelData['pay_info']);

		CouponPayService::pay($modelData,true);

	}

}