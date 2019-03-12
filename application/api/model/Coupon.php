<?php

namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\lib\exception\ErrorMessage;



class Coupon extends Model
{


    public static function dealAdd($data)
    {


        $standard = [
            'coupon_no'=>'',
            'title'=>'',
            'description'=>'',
            'content'=>'',
            'mainImg'=>[],
            'bannerImg'=>[],
            'price'=>0,
            'score'=>0,
            'value'=>0,
            'discount'=>100,
            'condition'=>0,
            'stock'=>0,
            'sales_num'=>0,
            'type'=>'',
            'valid_time'=>'',
            'onShelf'=>'',
            'limit'=>'',
            'use_limit'=>'',
            'user_no'=>'',
            'status'=>1,
            'create_time'=>time(),
            'thirdapp_id'=>'',
            'user_type'=>'',
            'img_array'=>[],
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