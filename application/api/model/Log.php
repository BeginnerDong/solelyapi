<?php

namespace app\api\model;
use think\Model;
use app\api\model\User;
use app\api\model\Order;


class Log extends BaseModel
{

    public static function dealAdd($data)
    {   
        
        $standard = ['title'=>'','result'=>'','content'=>'','create_time'=>time(),'user_id'=>'','status'=>1,'thirdapp_id'=>'','type'=>'','user_no'=>'','order_no'=>'','user_type'=>''];
        if(isset($data['data']['user_no'])){
            $res = User::get(['user_no' => $data['data']['user_no']]);
            if($res){
                $data['data']['user_type'] = $res['user_type'];
            }else{
                throw new ErrorMessage([
                    'msg' => '关联user信息有误',
                ]);
            };
        };
        if(isset($data['data']['order_no'])){
            $res = Order::get(['order_no' => $data['data']['order_no']]);
            if($res){
                $data['data']['user_type'] = $res['user_type'];
            }else{
                throw new ErrorMessage([
                    'msg' => '关联user信息有误',
                ]);
            };
        };
        $data['data'] = chargeBlank($standard,$data['data']);
        return $data;
        
    }
    public static function dealGet($data)
    {   

        return $data;
        
    }

    public static function dealUpdate($data)
    {   

    	return $data;
        
    }

    public static function dealRealDelete($data)
    {   

    	return $data;
        
    }


}