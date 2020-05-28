<?php

namespace app\api\model;



use think\Model;

use app\api\model\User;
use app\api\model\OrderItem;
use app\api\model\Product;
use app\api\model\Sku;



class Order extends BaseModel
{

	public static function dealAdd($data)
	{

		$standard = [
			'order_no'=>'',
			'pay'=>[],
			'price'=>'',
			'snap_address'=>[],
			'express'=>[],
			'pay_status'=>0,
			'type'=>'',
			'prepay_id'=>'',
			'wx_prepay_info'=>[],
			'order_step'=>0,
			'transport_status'=>0,
			'group_status'=>0,
			'transaction_id'=>'',
			'refund_no'=>'',
			'isrefund'=>'',
			'create_time'=>time(),
			'invalid_time'=>'',
			'start_time'=>'',
			'end_time'=>'',
			'update_time'=>'',
			'finish_time'=>'',
			'delete_time'=>'',
			'passage1'=>'',
			'passage2'=>'',
			'passage_array'=>[],
			'status'=>1,
			'thirdapp_id'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'express_info'=>'',
			'payAfter'=>[],
			'standard'=>0,
			'discount'=>0,
			'balance'=>0,
			'group_no'=>'',
			'group_leader'=>'',
			'pay_no'=>'',
			'limit'=>'',
			'level'=>'',
			'parent_no'=>'',
			'product_id'=>'',
			'sku_id'=>'',
			'title'=>'',
			'unit_price'=>'',
			'count'=>'',
			'isremark'=>'',
			'set_no'=>'',
			'index'=>0,
			'day_time'=>0,
			'phone'=>'',
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

		foreach ($data as $key => $value) {
			
			$value = resDeal([$value])[0];
			$child = resDeal(Order::all(['parent_no' => $value['order_no']]));
			
			foreach($child as $key_i => $value_i){
				$orderItems = resDeal((new OrderItem())->where([
								'order_no' => $value_i['order_no'],
								'status' => 1
							])->select());
				$child[$key_i]['orderItem'] =  $orderItems;
			};
			
			foreach($child as $key_c => $value_c){
				//组合套餐子级订单分组
				// if(empty($data[$key]['child'][$value_c['index']])){
				// 	$data[$key]['child'][$value_c['index']][0] = $value_c;
				// }else{
				// 	array_push($data[$key]['child'][$value_c['index']],$value_c);
				// };
				//一般项目子级订单拼接
				if(empty($data[$key]['child'])){
					$data[$key]['child'][0] = $value_c;
				}else{
					array_push($data[$key]['child'],$value_c);
				};
			};
		};

		return $data;

	}



	public static function dealUpdate($data)
	{

		if(isset($data['data']['status'])&&$data['data']['status']==-1){

			$res = resDeal((new Order())->where($data['searchItem'])->select());

			foreach ($res as $key => $value) {

				$orderItemRes = (new OrderItem())->where([

					'order_no' => $res[$key]['order_no'],

					'status' => 1

				])->select();

				foreach ($orderItemRes as $c_key => $c_value) {
					
					(new OrderItem())->save(

						['status'  => -1],

						['id' => $c_value['id']]

					);
				};

			};  

		};

		if(isset($data['data']['status'])&&$data['data']['status']==1){

			throw new ErrorMessage([

				'msg' => '请重新下单',

			]);

		};
		return $data;

	}



	public static function dealRealDelete($data)
	{

		$res = (new Order())->where($data['searchItem'])->select();

		foreach ($res as $key => $value) {

			OrderItem::destroy(['order_no' => $res[$key]['order_no']]);

		};

	}

}