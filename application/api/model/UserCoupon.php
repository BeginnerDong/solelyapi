<?php

namespace app\api\model;

use think\Model;

use app\api\model\User;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

class UserCoupon extends Model
{


	public static function dealAdd($data)
	{

		$standard = [
			'price'=>0,
			'score'=>0,
			'value'=>0,
			'discount'=>100,
			'condition'=>0,
			'pay_status'=>0,
			'type'=>'',
			'use_step'=>1,
			'invalid_time'=>'',
			'use_limit'=>'',
			'pay_no'=>'',
			'snap_coupon'=>'',
			'coupon_no'=>'',
			'create_time'=>time(),
			'update_time'=>time(),
			'thirdapp_id'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'status'=>1,
			'level'=>'',
			'parent_no'=>'',
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

		if(isset($data['data']['pay_status'])&&($data['data']['pay_status']==1||$data['data']['pay_status']==0)){

			$res = (new UserCoupon())->where($data['searchItem'])->find();

			$coupon = (new Coupon())->where(['coupon_no' => $res['coupon_no']])->find();

			if ($coupon) {

				if ($res['pay_status']==0&&$data['data']['pay_status']==1) {
					(new Coupon())->save(['sale_count' => $coupon['sale_count']+1,'stock' => $coupon['stock']-1],['id' => $coupon['id']]);
				}

				if ($res['pay_status']==1&&$data['data']['pay_status']==0) {
					(new Coupon())->save(['sale_count' => $coupon['sale_count']-1,'stock' => $coupon['stock']+1],['id' => $coupon['id']]);
				}
				
			}

		};

		return $data;

	}


	public static function dealRealDelete($data)
	{

		return $data;

	}

}