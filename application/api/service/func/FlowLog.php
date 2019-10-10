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

	public static function checkIsPayAll($data){

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
				$flowList = BeforeModel::CommonGet('FlowLog',$modelData);
				if(count($flowList['data'])>0){
					foreach ($flowList['data'] as $key => $value) {
						$flowPrice += abs($value['count']);
					};
				};

				/*父级订单需要加上子级流水*/
				if($orderInfo['parent']==1){
					$modelData = [];
					$modelData['searchItem']['parent_no'] = $flowInfo['order_no'];
					$modelData['searchItem']['status'] = 1;
					$childFlow = BeforeModel::CommonGet('FlowLog',$modelData);
					if(count($childFlow['data'])>0){
						foreach ($childFlow['data'] as $key_c => $value_c) {
							$flowPrice += abs($value_c['count']);
						};
					};
				}

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
                }
				
				/*子级订单检验父级订单支付状态*/
				if($orderInfo['parent']==2){
					
					$modelData = [];
					$modelData['searchItem']['order_no'] = $orderInfo['parent_no'];
					$parentOrder = BeforeModel::CommonGet('Order',$modelData);
					if(count($parentOrder['data'])>0){
					    $parentOrder = $parentOrder['data'][0];
						$parentPrice = abs($parentOrder['price']);
						if($parentPrice>0){
							$parentFlow = 0;
							//获取订单流水信息
							$modelData = [];
							$modelData['searchItem']['order_no'] = $parentOrder['order_no'];
							$modelData['searchItem']['status'] = 1;
							$flowList = BeforeModel::CommonGet('FlowLog',$modelData);
							if(count($flowList['data'])>0){
								foreach ($flowList['data'] as $key => $value) {
									$parentFlow += abs($value['count']);
								};
							};
							/*父级订单需要加上子级流水*/
							$modelData = [];
							$modelData['searchItem']['parent_no'] = $parentOrder['order_no'];
							$modelData['searchItem']['status'] = 1;
							$childFlow = BeforeModel::CommonGet('FlowLog',$modelData);
							if(count($childFlow['data'])>0){
								foreach ($childFlow['data'] as $key_c => $value_c) {
									$parentFlow += abs($value_c['count']);
								};
							};
							if ($parentPrice == $parentFlow){
							
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
                    'pay_no'=>$flowInfo['pay_no']
                ],
            ];
            $couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
            if(count($couponInfo['data'])>0){
                $couponPrice = abs($couponInfo['data'][0]['price']);
            }else{
                $couponPrice = 0;
            }

            if ($couponPrice > 0) {
                //获取流水信息
                $modelData = [];
                $modelData = [
                    'searchItem'=>[
                        'pay_no'=>$flowInfo['pay_no']
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
                            'id'=>$couponInfo['data'][0]['id']
                        ],
                    ];
                    $modelData['FuncName'] = 'update';
                    $modelData['data']['pay_status'] = 1;
                    $upCoupon = BeforeModel::CommonSave('UserCoupon',$modelData);
                }
            }
        }

	}

}