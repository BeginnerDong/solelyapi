<?php

namespace app\api\service\beforeModel;

use app\api\model\Common as CommonModel;
use think\Exception;
use think\Model;
use think\Cache;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

class FlowLog {

	public static function checkIsPayAll($data){

		if ($data['FuncName']=="add"&&!isset($data['data']['status'])&&isset($data['data']['pay_no'])&&!empty($data['data']['pay_no'])) {
			
			//获取订单信息
            $modelData = [];
            $modelData = [
                'searchItem'=>[
                    'pay_no'=>$data['data']['pay_no']
                ],
            ];
            $orderInfo = CommonModel::CommonGet('Order',$modelData);
            if(count($orderInfo['data'])>0){
                $orderPrice = abs($orderInfo['data'][0]['price']);
                $orderInfo = $orderInfo['data'][0];
            }else{
                $orderPrice = -1;
            }

            if ($orderPrice >= 0) {
                //获取流水信息
                $modelData = [];
                $modelData = [
                    'searchItem'=>[
                        'pay_no'=>$data['data']['pay_no']
                    ],
                ];
                $flowList = CommonModel::CommonGet('FlowLog',$modelData);
                $flowPrice = 0;
                //加上此次的流水金额
                $flowPrice += abs($data['data']['count']);
                if(count($flowList['data'])>0){
                    foreach ($flowList['data'] as $key => $value) {
                        $flowPrice += abs($value['count']);
                    };
                };

                if ($orderPrice == $flowPrice) {

                    $modelData = []; 
                    $modelData = [
                        'searchItem'=>[
                            'id'=>$orderInfo['id']
                        ],
                    ];
                    $modelData['FuncName'] = 'update';
                    $modelData['data']['pay_status'] = 1;

                    //执行payAfter
                    if(isset($orderInfo['payAfter'])&&!empty($orderInfo['payAfter'])){
                        if (isset($data['saveAfter'])) {
                            $data['saveAfter'] = array_merge($data['saveAfter'],$orderInfo['payAfter']);
                        }else{
                            $data['saveAfter'] = $orderInfo['payAfter'];
                        }
                    };

                    $updateOrder = CommonModel::CommonSave('Order',$modelData);
                }
            }

		}

        return $data;

	}

}