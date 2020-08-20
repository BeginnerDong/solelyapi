<?php

namespace app\api\model;
use think\Model;
use app\api\model\relation;
use app\api\model\Label;
use app\api\model\Product;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class Sku extends Model{

	public static function dealAdd($data)
	{
		
		$standard = [
			'sku_no'=>'',
			'title'=>'',
			'label_array'=>'',
			'sku_item'=>[],
			'product_no'=>'',
			'stock'=>'',
			'price'=>'',
			'group_price'=>'',
			'o_price'=>'',
			'mainImg'=>[],
			'description'=>'',
			'listorder'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'status'=>1,
			'thirdapp_id'=>'',
			'user_no'=>'',
			'on_shelf'=>'',
			'category_id'=>'',
			'spu_array'=>[],
			'spu_item'=>[],
			'behavior'=>'',
			'sale_count'=>'',
			'limit'=>'',
			'start_time'=>'',
			'end_time'=>'',
			'use_limit'=>'',
			'duration'=>'',
			'standard'=>'',
			'is_group'=>'',
			'group_stock'=>'',
			'score'=>'',
			'is_date'=>0,
			'img_array'=>[],
		];
		
		$res = Product::get(['product_no' => $data['data']['product_no']]);
		$res = resDeal([$res])[0];
		if(!$res){
			throw new ErrorMessage([
				'msg' => '关联信息有误',
			]);
		};
		foreach ($data['data']['sku_item'] as $key => $value) {
			$data['data']['sku_item'][$key] = (int)$value;
		};
		$mergeArray = array_keys(array_flip($res['sku_item']) + array_flip($data['data']['sku_item']));
		Product::where('id', $res['id'])->update(['sku_item' => json_encode($mergeArray)]);
		
		$data['data']['category_id'] = $res['category_id'];
		$data['data']['spu_array'] = $res['spu_array'];
		$data['data']['sku_no'] = makeSkuNo($res);

		$data['data'] = chargeBlank($standard,$data['data']);
		return $data;
		
	}



	public static function dealGet($data)
	{

		$product_array = [];

		foreach ($data as $key => $value) {
			array_push($product_array,$value['product_no']);
		};

		$product = resDeal((new product())
			->where('product_no','in',$product_array)
			->select()
		);

		foreach ($product as $key => $value) {
			$sku_array = $value['sku_array'];
			array_push($sku_array,$value['category_id']);
			$label = resDeal((new Label())->where('id','in',$sku_array)->whereOr('parentid','in',$sku_array)->select());
			$label = clist_to_tree($label);
			$label = changeIndexArray('product_no',$label);
			$product[$key]['label'] = $label;
		};

		$product = changeIndexArray('product_no',$product);
		
		foreach ($data as $key => $value) {
			if(isset($product[$value['product_no']])){
				$data[$key]['product'] = $product[$value['product_no']];  
			}else{
				$data[$key]['product'] = [];
			};
		};
		
		return $data;
		
	}



	public static function dealUpdate($data)
	{

		if(array_key_exists("sku_item",$data['data'])&&!empty($data['data']['sku_item'])){
			$sku = Sku::get($data['searchItem']);
			$product = resDeal([Product::get(['product_no'=>$sku['product_no']])])[0];
			if(!$product){
				throw new ErrorMessage([
					'msg' => '关联product信息失败',
				]);
			};
			$label = $data['data']['sku_item'];
			$search = [];
			$search['product_no'] = $sku['product_no'];
			$search['status'] = 1;
			$sku_array = resDeal((new Sku())->where($search)->select());
			if($sku_array){
				foreach($sku_array as $key_s => $value_s){
					if($value_s['id']!=$sku['id']){
						foreach($value_s['sku_item'] as $value_c){
							if(!in_array($value_c,$label)){
								array_push($label,$value_c);
							};
						};
					};
				};
			};

			$res = Product::where(['product_no'=>$sku['product_no']])->update(['sku_item' => json_encode($label)]);
		};
		
		
		//删除SKU，关联删除product上的sku_item
		if(isset($data['data']['status'])&&$data['data']['status']==-1){
			
			$sku = Sku::get($data['searchItem']);
			$label = [];
			$search = [];
			$search['product_no'] = $sku['product_no'];
			$search['status'] = 1;
			$sku_array = resDeal((new Sku())->where($search)->select());
			if($sku_array){
				foreach($sku_array as $key_s => $value_s){
					if($value_s['id']!=$sku['id']){
						foreach($value_s['sku_item'] as $value_c){
							if(!in_array($value_c,$label)){
								array_push($label,$value_c);
							};
						};
					};
				};
			};
			$res = Product::where(['product_no'=>$sku['product_no']])->update(['sku_item' => json_encode($label)]);

		};
	 
	}



	public static function dealRealDelete($data)
	{

	}

}