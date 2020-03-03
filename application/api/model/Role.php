<?php

namespace app\api\model;

use think\Model;

use app\api\model\User;

use app\lib\exception\ErrorMessage;



class Role extends Model
{


	public static function dealAdd($data)
	{

		$standard = [
			'name'=>'',
			'status'=>1,
			'create_time'=>time(),
			'update_time'=>time(),
			'delete_time'=>'',
			'thirdapp_id'=>'',
			'user_type'=>'',
			'user_no'=>'',
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