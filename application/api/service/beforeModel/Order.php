<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Order {


	public static function deal($data)
	{

		/*同意退款*/
		if($data['FuncName']=='update'&&isset($data['data']['order_step'])&&$data['data']['order_step']==2){
			
			$orderInfo = CommonModel::CommonGet('Order',$data);
			if (count($orderInfo['data'])==0) {
				throw new ErrorMessage([
					'msg' => '订单不存在',
				]);
			};
			$orderInfo = $orderInfo['data'][0];
			if($orderInfo['pay_status']!=1){
				throw new ErrorMessage([
					'msg' => '订单未支付',
				]);
			};
			if($orderInfo['order_step']==2){
				throw new ErrorMessage([
					'msg' => '订单已退款',
				]);
			};
			
			self::refund($orderInfo);
			
			$data['data']['pay_status'] = -1;
		};

        return $data;

	}
	
	
	/*订单退款*/
	public static function refund($orderInfo)
	{
		
		/*获取订单流水*/
		$modelData = [];
		$modelData['searchItem']['relation_table'] = 'order';
		$modelData['searchItem']['order_no'] = $orderInfo['order_no'];
		$flows = CommonModel::CommonGet('FlowLog',$modelData);
		if(count($flows['data'])>0){
			$modelData = [];
			$modelData['FuncName'] = 'update';
			$modelData['searchItem']['relation_table'] = 'order';
			$modelData['searchItem']['order_no'] = $orderInfo['order_no'];
			$modelData['data']['status'] = -1;
			$refund = CommonModel::CommonSave('FlowLog',$modelData);
		};
		/*搜索子单流水*/
		$modelData = [];
		$modelData['searchItem']['parent_no'] = $orderInfo['order_no'];
		$children = CommonModel::CommonGet('Order',$modelData);
		if(count($children['data'])>0){
			$order_no = [];
			foreach($children['data'] as $key_o => $value_o){
				array_push($order_no,$value_o['order_no']);
			};
			$modelData = [];
			$modelData['searchItem']['relation_table'] = 'order';
			$modelData['searchItem']['order_no'] = ['in',$order_no];
			$childFlows = CommonModel::CommonGet('FlowLog',$modelData);
			if(count($childFlows['data'])>0){
				$modelData = [];
				$modelData['FuncName'] = 'update';
				$modelData['searchItem']['relation_table'] = 'order';
				$modelData['searchItem']['order_no'] = ['in',$order_no];
				$modelData['data']['status'] = -1;
				$refund = CommonModel::CommonSave('FlowLog',$modelData);
			};
		};
		
	}

}