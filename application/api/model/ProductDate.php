<?php

namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class ProductDate extends Model
{

	public static function dealAdd($data)
	{

		$standard = [
			'product_no'=>'',
			'sku_no'=>'',
			'type'=>'',
			'price'=>0,
			'group_price'=>0,
			'day_time'=>'',
			'stock'=>0,
			'group_stock'=>0,
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'thirdapp_id'=>'',
			'status'=>1,
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