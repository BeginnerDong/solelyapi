<?php

namespace app\api\service\func;


use app\api\service\beforeModel\Common as BeforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class FlowLog {

	public static function checkIsPayAll($data)
	{

		$flowInfo = BeforeModel::CommonGet('FlowLog',$data);

		if (count($flowInfo['data'])==0) {
			
			throw new ErrorMessage([
				'msg'=>'流水信息有误'
			]);

		}

		$flowInfo = $flowInfo['data'][0];

		/**
		 * 检查订单支付是否完成
		 */
		if (isset($flowInfo['relation_table'])&&($flowInfo['relation_table']=='order')) 
		{
			
			//获取订单信息
			$modelData = [];
			$modelData = [
				'searchItem'=>[
					'order_no'=>$flowInfo['order_no']
				],
			];
			$orderInfo = BeforeModel::CommonGet('Order',$modelData);
			if(count($orderInfo['data'])>0){
				$orderPrice = abs($orderInfo['data'][0]['price']);
				$orderInfo = $orderInfo['data'][0];
			}else{
				$orderPrice = -1;
			}
			
			//删除订单锁
			// Cache::rm($orderInfo['order_no']);

			if ($orderPrice >= 0) {
				
				$flowPrice = 0;
				//获取订单流水信息
				$modelData = [];
				$modelData['searchItem']['order_no'] = $flowInfo['order_no'];
				$modelData['searchItem']['status'] = 1;
				$modelData['searchItem']['account'] = 1;
				$flowList = BeforeModel::CommonGet('FlowLog',$modelData);
				if(count($flowList['data'])>0){
					foreach ($flowList['data'] as $key => $value) {
						$flowPrice += abs($value['count']);
					};
				};
				
				/*float金额转换成int比较大小*/
				$orderPrice = (int)($orderPrice*100);
				$flowPrice = (int)($flowPrice*100);

				if ($orderPrice == $flowPrice){

					$modelData = []; 
					$modelData['FuncName'] = 'update';
					$modelData['searchItem']['id'] = $orderInfo['id'];
					$modelData['data']['pay_status'] = 1;

					//执行payAfter
					if(isset($orderInfo['payAfter'])&&!empty($orderInfo['payAfter'])){
						$modelData['saveAfter'] = $orderInfo['payAfter'];
					};

					$updateOrder = BeforeModel::CommonSave('Order',$modelData);
					
					$modelData = [];
					$modelData['searchItem']['id'] = $orderInfo['id'];
					/*计算库存*/
					if($orderInfo['count']>0){
						BeforeModel::dealStock($modelData,'reduce');
					};
					/*团购单判断*/
					if(!empty($orderInfo['group_no'])){
						self::dealGroup($modelData);
					};
				}
				
				/*子级订单检验父级订单支付状态*/
				if(!empty($orderInfo['parent_no'])){
					
					$modelData = [];
					$modelData['searchItem']['parent_no'] = $orderInfo['parent_no'];
					$childs = BeforeModel::CommonGet('Order',$modelData);
					
					$modelData = [];
					$modelData['searchItem']['order_no'] = $orderInfo['parent_no'];
					$parentOrder = BeforeModel::CommonGet('Order',$modelData);
					
					if(count($childs['data'])>0&&count($parentOrder['data'])>0){
						
						$parentOrder = $parentOrder['data'][0];
						$pay_all = true;
						foreach($childs['data'] as $key_c => $value_c){
							if($value_c['pay_status']!=1){
								$pay_all = false;
							};
						};

						if($pay_all){
						
							$modelData = []; 
							$modelData['FuncName'] = 'update';
							$modelData['searchItem']['id'] = $parentOrder['id'];
							$modelData['data']['pay_status'] = 1;
						
							//执行payAfter
							if(isset($parentOrder['payAfter'])&&!empty($parentOrder['payAfter'])){
								$modelData['saveAfter'] = $parentOrder['payAfter'];
							};
						
							$updateOrder = BeforeModel::CommonSave('Order',$modelData);
						};
					};
				};
				
				/*父级订单检测子级订单支付状态*/
				$modelData = [];
				$modelData['searchItem']['parent_no'] = $orderInfo['order_no'];
				$modelData['searchItem']['pay_status'] = 0;
				$children = BeforeModel::CommonGet('Order',$modelData);
				if(count($children['data'])>0){
					foreach($children['data'] as $key => $value){
						$modelData = [];
						$modelData['FuncName'] = 'update';
						$modelData['searchItem']['id'] = $value['id'];
						$modelData['data']['pay_status'] = 1;
						if(isset($value['payAfter'])&&!empty($value['payAfter'])){
							$modelData['saveAfter'] = $value['payAfter'];
						};
						$upChild = BeforeModel::CommonSave('Order',$modelData);
						/*计算库存*/
						if($value['count']>0){
							$modelData = [];
							$modelData['searchItem']['id'] = $value['id'];
							BeforeModel::dealStock($modelData,'reduce');
						};
					};
				};
			};

		}

		/**
		 * 检查优惠券支付是否完成
		 */
		if (isset($flowInfo['relation_table'])&&($flowInfo['relation_table']=='coupon')) 
		{

			//获取优惠券信息
			$modelData = [];
			$modelData = [
				'searchItem'=>[
					'id'=>$flowInfo['relation_id']
				],
			];
			$couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
			if(count($couponInfo['data'])>0){
				$couponPrice = abs($couponInfo['data'][0]['price']);
				$couponInfo = $couponInfo['data'][0];
			}else{
				$couponPrice = 0;
			}

			if ($couponPrice > 0) {
				//获取流水信息
				$modelData = [];
				$modelData = [
					'searchItem'=>[
						'relation_id'=>$flowInfo['relation_id']
					],
				];
				$flowList = BeforeModel::CommonGet('FlowLog',$modelData);

				$flowPrice = 0;
				if(count($flowList['data'])>0){
					foreach ($flowList['data'] as $key => $value) {
						$flowPrice += abs($value['count']);
					}
				}

				if ($couponPrice == $flowPrice) {
					$modelData = [];
					$modelData = [
						'searchItem'=>[
							'id'=>$couponInfo['id']
						],
					];
					$modelData['FuncName'] = 'update';
					$modelData['data']['pay_status'] = 1;
					$upCoupon = BeforeModel::CommonSave('UserCoupon',$modelData);
					
					/*子级订单检验父级订单支付状态*/
					if(!empty($couponInfo['parent_no'])){
						
						$modelData = [];
						$modelData['searchItem']['parent_no'] = $couponInfo['parent_no'];
						$childs = BeforeModel::CommonGet('UserCoupon',$modelData);
						
						$modelData = [];
						$modelData['searchItem']['id'] = $couponInfo['parent_no'];
						$parentCoupon = BeforeModel::CommonGet('UserCoupon',$modelData);
						
						if(count($childs['data'])>0&&count($parentCoupon['data'])>0){
							
							$parentCoupon = $parentCoupon['data'][0];
							$pay_all = true;
							foreach($childs['data'] as $key_c => $value_c){
								if($value_c['pay_status']!=1){
									$pay_all = false;
								};
							};
					
							if($pay_all){
								$modelData = [];
								$modelData['FuncName'] = 'update';
								$modelData['searchItem']['id'] = $parentCoupon['id'];
								$modelData['data']['pay_status'] = 1;
								$updateCoupon = BeforeModel::CommonSave('UserCoupon',$modelData);
							};
						};
					};
				};
			};
		};
	}


	/*团购逻辑判断*/
	public static function dealGroup($data)
	{
		
		$orderInfo = BeforeModel::CommonGet('Order',$data);
		$orderInfo = $orderInfo['data'][0];
		
		$modelData = [];
		$modelData['searchItem']['group_no'] = $orderInfo['group_no'];
		$modelData['searchItem']['pay_status'] = 1;
		$modelData['searchItem']['group_status'] = ['in',[1,2]];
		$groups = BeforeModel::CommonGet('Order',$modelData);
		if(count($groups['data'])>=$orderInfo['standard']){
			/*成团*/
			foreach($groups['data'] as $key => $value){
				if($value['group_status']==1){
					$modelData = [];
					$modelData['FuncName'] = 'update';
					$modelData['searchItem']['id'] = $value['id'];
					$modelData['data']['group_status'] = 2;
					$upOrder = BeforeModel::CommonSave('Order',$modelData);
				};
			};
		};
	
	}

}