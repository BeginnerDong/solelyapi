<?php

namespace app\api\model;

use think\Model;
use app\api\model\User;

use app\lib\exception\ErrorMessage;

class File extends Model
{

    public static function dealAdd($data)
    {   
        
        $standard = ['title'=>'','path'=>'','thirdapp_id'=>'','size'=>0,'create_time'=>time(),'type'=>1,'user_no'=>'','relation_table'=>'','relation'=>'','status'=>1,'relation_id'=>'','prefix'=>'','user_type'=>'','origin'=>'','behavior'=>'','param'=>''];
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