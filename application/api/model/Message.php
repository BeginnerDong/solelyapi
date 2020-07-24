<?php

namespace app\api\model;


use think\Model;
use app\api\model\User;
use app\lib\exception\ErrorMessage;

class Message extends BaseModel
{

	public static function dealAdd($data)
	{
		
		$standard = [
			'title'=>'',
			'description'=>'',
			'keywords'=>'',
			'phone'=>'',
			'gender'=>'',
			'content'=>'',
			'class'=>'',
			'score'=>'',
			'mainImg'=>[],
			'relation_table'=>'',
			'relation_id'=>'',
			'relation_user'=>'',
			'type'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'thirdapp_id'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'passage1'=>'',
			'passage2'=>'',
			'passage_array'=>[],
			'status'=>1,
			'product_no'=>'',
			'order_no'=>'',
			'behavior'=>'',
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