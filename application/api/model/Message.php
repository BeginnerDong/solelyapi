<?php

namespace app\api\model;


use think\Model;
use app\api\model\User;
use app\lib\exception\ErrorMessage;

class Message extends BaseModel
{

    public static function dealAdd($data){   
        
        $standard = ['title'=>'','keywords'=>'','phone'=>'','content'=>'','class'=>'','score'=>'','mainImg'=>[],'relation_table'=>'','relation_id'=>'','type'=>'','user_no'=>'','thirdapp_id'=>'','create_time'=>time(),'delete_time'=>'','passage1'=>'','passage_array'=>[],'status'=>1,'product_no'=>'','order_no'=>'','gender'=>'','update_time'=>''];
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

