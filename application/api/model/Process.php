<?php

namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\lib\exception\ErrorMessage;



class Process extends Model
{


    public static function dealAdd($data)
    {   

        
        $standard = [
            'name'=>'',
            'process_type'=>0,
            'develop_type'=>0,
            'function_type'=>0,
            'step'=>0,
            'content'=>'',
            'description'=>'',
            'mainImg'=>[],
            'project_no'=>'',
            'user_no'=>'',
            'status'=>1,
            'create_time'=>time(),
            'thirdapp_id'=>'',
            'user_type'=>'',
            'img_array'=>[]
        ];

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