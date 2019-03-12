<?php


namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\api\model\UserInfo;
use app\api\model\Order;

use app\lib\exception\ErrorMessage;

use app\api\service\base\WxPay;

class FlowLog extends Model

{

    public static function dealAdd($data)

    {   

        $standard = [
            'type'=>'',
            'count'=>'',
            'relation_id'=>'',
            'standard_id'=>'',
            'create_time'=>time(),
            'delete_time'=>'',
            'status'=>1,
            'trade_info'=>'',
            'relation_table'=>'',
            'thirdapp_id'=>'',
            'order_no'=>'',
            'pay_no'=>'',
            'product_no'=>'',
            'user_no'=>'',
            'user_type'=>'',
            'update_time'=>'',
            'behavior'=>1,
            'payInfo'=>'',
            'extra_info'=>'',
        ];

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