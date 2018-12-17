<?php



namespace app\api\model;





use think\Model;

use app\api\model\User;

use app\api\model\UserInfo;
use app\api\model\Order;
use app\api\model\Common as CommonModel;

use app\lib\exception\ErrorMessage;

use app\api\service\base\WxPay;



class FlowLog extends Model

{



    public static function dealAdd($data)

    {   

        

        $standard = ['type'=>'','count'=>'','relation_id'=>'','create_time'=>time(),'delete_time'=>'','status'=>1,'trade_info'=>'','relation_table'=>'','thirdapp_id'=>'','payInfo'=>'','product_no'=>'','order_no'=>'','user_no'=>'','user_type'=>0,'behavior'=>1,'update_time'=>''];

        if(isset($data['data']['user_no'])){

            $res = User::get(['user_no' => $data['data']['user_no']]);

            $UserInfo = UserInfo::get(['user_no' => $data['data']['user_no']]);

            if($res){

                $data['data']['user_type'] = $res['user_type'];

            }else{

                throw new ErrorMessage([

                    'msg' => '关联user信息有误',

                ]);

            };

        };

        $data['data'] = chargeBlank($standard,$data['data']);

        if(isset($data['data']['type'])&&($data['data']['type']==2||$data['data']['type']==3)&&isset($data['data']['status'])&&($data['data']['status']==1)){

            if($data['data']['type']==2){

                $res = UserInfo::where('user_no', $data['data']['user_no'])->update(['balance' => $data['data']['count']+$UserInfo['balance']]);

            }else if($data['data']['type']==3){

                $res = UserInfo::where('user_no', $data['data']['user_no'])->update(['score' => $data['data']['count']+$UserInfo['score']]);

            };

        };

        //检查订单支付支付是否完成
        if (($data['data']['status']==1)&&isset($data['data']['pay_no'])&&!empty($data['data']['pay_no'])) {
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
            }else{
                $orderPrice = 0;
            }

            if ($orderPrice > 0) {
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
                    }
                }

                if ($orderPrice == $flowPrice) {
                    $modelData = [];
                    $modelData = [
                        'searchItem'=>[
                            'id'=>$orderInfo['data'][0]['id']
                        ],
                    ];
                    $modelData['FuncName'] = 'update';
                    $modelData['data']['pay_status'] = 1;
                    $flowList = CommonModel::CommonSave('Order',$modelData);
                    // $res = Order::where('id', $orderInfo['data'][0]['id'])->update(['pay_status'=>1]);
                }
            }
        }

        return $data;

    }



    public static function dealGet($data){   

        return $data;

    }



    public static function dealUpdate($data)

    {   

        
        if(isset($data['data']['type'])||isset($data['data']['count'])){
            throw new ErrorMessage([
                'msg' => '不允许编辑的字段',
            ]);
        };

        $FlowLogInfo = FlowLog::get($data['searchItem']);

        $UserInfo = UserInfo::get([
            'status'=>1,
            'user_no'=>$FlowLogInfo['user_no']
        ]);

        //流水执行
        if (isset($data['data']['status'])&&$data['data']['status']==1&&$FlowLogInfo['status']!=1) {

            if($FlowLogInfo['type']==2){

                $res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['balance' => $FlowLogInfo['count']+$UserInfo['balance']]);

            }else if($FlowLogInfo['type']==3){

                $res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['score' => $FlowLogInfo['count']+$UserInfo['score']]);

            };
        }

        //流水退还
        if(isset($data['data']['status'])&&$data['data']['status']==-1&&$FlowLogInfo['status']==1){ 

            if($FlowLogInfo['type']==2){

                $res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['balance' => $UserInfo['balance']-$FlowLogInfo['count']]);

            }else if($FlowLogInfo['type']==3){

                $res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['score' => $UserInfo['score']-$FlowLogInfo['count']]);

            }else if($FlowLogInfo['type']==1){

                WxPay::refundOrder($FlowLogInfo['id']);

            }else if($FlowLogInfo['type']==6){

                $cardInfo = Order::get(['order_no'=>$FlowLogInfo['relation_id']]);
                $res = Order::where('order_no',$FlowLogInfo['relation_id'])->update(['balance' => $cardInfo['balance']-$FlowLogInfo['count']]);

            };

        };

    	return $data;

    }


    public static function dealRealDelete($data)

    {   

    	return $data;

    }
}