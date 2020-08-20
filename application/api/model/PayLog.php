<?php

namespace app\api\model;
use think\Model;
use app\api\model\User;
use app\api\model\Order;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class PayLog extends BaseModel
{

	public static function dealAdd($data)
	{
		
		$standard = [
			'title'=>'',
			'price'=>0,
			'result'=>'',
			'content'=>'',
			'type'=>'',
			'order_no'=>'',
			'pay_no'=>'',
			'trade_no'=>'',
			'transaction_id'=>'',
			'behavior'=>'',
			'pay_info'=>'',
			'prepay_id'=>'',
			'wx_prepay_info'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'thirdapp_id'=>'',
			'user_type'=>'',
			'user_no'=>'',
			'status'=>1,
			'parent_no'=>'',
			'saveFunction'=>[],
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
		if(isset($data['data']['order_no'])){
			$res = Order::get(['order_no' => $data['data']['order_no']]);
			if($res){
				$data['data']['user_type'] = $res['user_type'];
			}else{
				throw new ErrorMessage([
					'msg' => '关联order信息有误',
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