<?php

namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class Coupon extends Model
{


	public static function dealAdd($data)
	{

		$standard = [
			'coupon_no'=>makeProductNo($data['data']['thirdapp_id']),
			'title'=>'',
			'description'=>'',
			'content'=>'',
			'mainImg'=>[],
			'bannerImg'=>[],
			'category_id'=>'',
			'price'=>0,
			'score'=>0,
			'value'=>0,
			'discount'=>100,
			'condition'=>0,
			'stock'=>0,
			'sale_count'=>0,
			'type'=>'',
			'valid_time'=>'',
			'on_shelf'=>'',
			'limit'=>'',
			'use_limit'=>'',
			'create_time'=>time(),
			'update_time'=>time(),
			'delete_time'=>'',
			'thirdapp_id'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'status'=>1,
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