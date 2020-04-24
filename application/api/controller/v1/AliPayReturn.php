<?php
/**
 * Created by dbm.
 * Author: dbm
 * Date: 2020/2/24
 * Time: 16:41
 */

namespace app\api\controller\v1;

use think\Db;
use think\Controller;

use app\api\service\base\Pay as PayService;
use app\api\service\base\AliPay as AliPayService;
use app\api\service\base\CouponPay as CouponPayService;
use app\api\service\func\FlowLog as FlowLogService;
use app\api\service\beforeModel\Common as BeforeModel;
use app\api\service\project\Solely as SolelyService;

use think\Request as Request;

use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class AliPayReturn extends Controller
{
	//支付回调
	public function receiveNotify(){

		$data = Request::instance()->param();
		
		//验证签名(未成功)
		//去掉sign与fund_bill_list反斜杠
		// $data['sign'] = stripslashes($data['sign']);
		// $data['fund_bill_list'] = stripslashes($data['fund_bill_list']);
		// $verify = AliPayService::verify($data);
		// if(!$verify){
		// 	//验证失败
		// 	echo"验签失败";
		// 	return;
		// };
		//检验信息
		$orderId = $data['out_trade_no'];
		$modelData = [];
		$modelData['getOne'] = 'true';
		$modelData['searchItem']['pay_no'] = $orderId;
		$payLog = BeforeModel::CommonGet('PayLog',$modelData);
		if(count($payLog['data'])>0){
			$payLog = $payLog['data'][0];
		}else{
			echo"支付订单不存在";
			return;
		};
		if($data['total_amount']!=$payLog['price']){
			echo"金额错误";
			return;
		};
		$modelData = [];
		$modelData['getOne'] = 'true';
		$modelData['searchItem']['id'] = $payLog['thirdapp_id'];
		$thirdInfo = BeforeModel::CommonGet('ThirdApp',$modelData);
		$thirdInfo = $thirdInfo['data'][0];
		if($data['seller_id']!=$thirdInfo['ali_seller_id']){
			return;
		};
		if($data['app_id']!=$thirdInfo['ali_appid']){
			return;
		};
		
		//开始支付回调逻辑....
		if($data['trade_status']=='TRADE_SUCCESS'||$data['trade_status']=='TRADE_FINISHED'){

			if(!empty($payLog)&&$payLog['trade_no']==$data['trade_no']){
				echo 'success';
				return;
			};
			
			$modelData = [];
			$modelData['searchItem'] = [
				'pay_no'=>$orderId
			];
			$modelData['data'] = array(
				'title'=>'支付宝支付回调成功',
				'trade_no'=>$data['trade_no'],
				'content'=>$data,
				'pay_no'=>$orderId,
				'update_time'=>time(),
			);
			$modelData['FuncName'] = 'update';
			$saveLog = BeforeModel::CommonSave('PayLog',$modelData);

			if($payLog['behavior']==1){
				echo 'success';
				return;
			};
			$TOTAL_FEE = $data['total_amount'];

			if (isset($payLog['pay_info'])&&isset($payLog['pay_info']['iscoupon'])&&!empty($payLog['pay_info']['iscoupon'])) {
				$modelData = [];
				$modelData['searchItem']['pay_no'] = $orderId;
				$couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
				$this->dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog);
				echo 'success';
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

			//记录支付宝支付回调日志
			$modelData = [];
			$modelData['FuncName'] = 'add';
			$modelData['data'] = array(
				'title'=>'支付宝支付',
				'result'=>$data['trade_status'],
				'content'=>$data,
				'pay_no'=>$data['out_trade_no'],
				'create_time'=>time(),
				'type'=>2,
			);
			$saveLog = BeforeModel::CommonSave('PayLog',$modelData);
		};
	}


	public function dealOrder($data,$orderInfo,$TOTAL_FEE,$payLog)
	{
		$modelData = [];
		$modelData['data'] = array(
			'type' => 4,
			'account' => 1,
			'count'=>-$TOTAL_FEE,
			'order_no'=>isset($orderInfo['order_no'])?$orderInfo['order_no']:'',
			'parent_no'=>isset($orderInfo['parent_no'])?$orderInfo['parent_no']:'',
			'pay_no'=>$payLog['pay_no'],
			'trade_info'=>'支付宝支付',
			'thirdapp_id'=>$payLog['thirdapp_id'],
			'user_no'=>$payLog['user_no'],
			'relation_table'=>'order',
		);
		
		$modelData['FuncName'] = 'add';
		$res = BeforeModel::CommonSave('FlowLog',$modelData);

		$modelData = [];
		$modelData['searchItem']['id'] = $res;
		FlowLogService::checkIsPayAll($modelData);

		if(!empty($payLog['saveFunction'])){
			SolelyService::saveFunction($payLog['saveFunction']);
		};

		$modelData = [];
		$modelData = $payLog;
		//取出第一次调取支付时记录的信息
		$modelData = array_merge($modelData,$payLog['pay_info']);
		$modelData['wxPayStatus'] = 1;
		$modelData['aliPayStatus'] = 1;
		$modelData['searchItem'] = [
			'order_no'=>$payLog['order_no']
		];
		unset($modelData['pay_info']);

		PayService::pay($modelData,true);
	}


	public function dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog)
	{
		$modelData = [];
		$modelData['data'] = array(
			'type' => 4,
			'count'=>-$TOTAL_FEE,
			'relation_id'=>$coupon_value['id'],
			'parent_no'=>isset($orderInfo['parent_no'])?$orderInfo['parent_no']:'',
			'pay_no'=>$payLog['pay_no'],
			'trade_info'=>'支付宝支付',
			'thirdapp_id'=>$payLog['thirdapp_id'],
			'user_no'=>$payLog['user_no'],
			'relation_table'=>'coupon',
		);
		
		$modelData['FuncName'] = 'add';
		$res = BeforeModel::CommonSave('FlowLog',$modelData);

		$modelData = [];
		$modelData['searchItem']['id'] = $res;
		FlowLogService::checkIsPayAll($modelData);

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