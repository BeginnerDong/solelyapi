<?php

namespace app\api\model;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

use think\Model;

class Label extends BaseModel
{

	public static function dealAdd($data)
	{
		$standard = [
			'title'=>'',
			'description'=>'',
			'parentid'=>'',
			'listorder'=>'',
			'type'=>'',
			'mainImg'=>[],
			'bannerImg'=>[],
			'thirdapp_id'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'status'=>1,
			'user_no'=>'',
			'passage1'=>'',
			'url'=>'',
			'img_array'=>[],
		];
		
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