<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\Func as FuncService;

use app\api\service\project\Solely as SolelyService;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Common {


	public static function CommonGet($dbTable,$data)
	{

		$data = self::CommonGetPro($data);

		if(!$data){
			$final['data'] = [];
			return $final;
		};
		
		/*检测过期优惠券*/
		if($dbTable=="UserCoupon"){
			
			self::checkCoupon($dbTable,$data);
			
		};

		$final = CommonModel::CommonGet($dbTable,$data);
		
		if(isset($data['saveFunction'])){
			
			SolelyService::saveFunction($data['saveFunction']);
			
		};

		$final['data'] = self::CommonGetAfter($data,$final['data']);

		$final['data'] = self::getLimit($data,$final['data']);

		/*商品拼接库存信息*/
		if($dbTable=="Product"||$dbTable=="Sku"){
			
			$final['data'] = self::checkStock($dbTable,$data,$final['data']);
			
		};

		if(isset($data['excelOutput'])){
			return exportExcel($data['excelOutput'],$final['data']);
		}else{
			return $final;
		};
		
	}



	public static function CommonSave($dbTable,$data)
	{
		
		$data = self::CommonGetPro($data);

		$data = FuncService::check($dbTable,$data);

		$data = self::CommonSavePro($data);

		$FuncName = $data['FuncName'];

		if ($FuncName=='update'){
			
			CommonModel::imgManage($dbTable,$data);

		}

		$finalRes = CommonModel::CommonSave($dbTable,$data);
		
		if(isset($data['saveFunction'])){
			
			SolelyService::saveFunction($data['saveFunction']);
			
		};

		if($FuncName=='update'){

			self::CommonSaveAfter($dbTable,$data);

		}else{
	  
			$data['searchItem'] = [
				'id'=>$finalRes
			];
			CommonModel::imgManage($dbTable,$data);
			self::CommonSaveAfter($dbTable,$data);
		};

		return $finalRes;

	}



	public static function CommonDelete($dbTable,$data)
	{

		$del = CommonModel::CommonDelete($dbTable,$data);
		
	}



	/**
	 * 前置搜索
	 * 分两层可以设置交/并集选项
	 * getBefore多个条件之间，在每个getBefore中设置type==merge为并集，默认交集
	 * 每个getBefore内的多个searchItem之间，在getBefore中设置searchType==merge为并集，默认交集
	 */
	public static function CommonGetPro($data)
	{
		if(isset($data['getBefore'])){
			$getBeforeData = [];
			$newSearchItem = [];
			foreach ($data['getBefore'] as $key => $value) {
				
				/*初始化每个getBefore的结果*/
				$search = [];
				$ids = [];
				$fixSearchItem = [];
				/*前表搜索出的数据结果*/
				$origin_data = [];
				
				if(is_array($value['key'])){
					$finalItem = '';
					foreach ($value['key'] as $cc_key => $cc_value) {
						if($cc_key==0){
							$finalItem = $getBeforeData[$value['key'][0]];
						}else{
							if ($finalItem&&isset($finalItem[$value['key'][$cc_key]])) {
								$finalItem = $finalItem[$value['key'][$cc_key]];
							}else{
								$finalItem = '';
							};
						};
					};
					if($finalItem){
						$fixSearchItem[$value['middleKey']] = [$value['condition'],$finalItem];
					};
				};
				
				$num = 0;
				foreach ($value['searchItem'] as $c_key => $c_value) {
					$num += 1;
					/*初始化每个searchItem的结果*/
					$searchItem = [];
					$map = [];
					if(!empty($fixSearchItem)){
						$map = $fixSearchItem;
					};
					$map[$c_key] = [$c_value[0],$c_value[1]];
					$modelData = [];
					$modelData['searchItem'] = $map;
					$res = CommonModel::CommonGet($value['tableName'],$modelData);
					$origin_data = array_merge($origin_data,$res['data']);
					/*记录结果待复用*/
					$getBeforeData[$key] = $res;
					foreach ($res['data'] as $ckey => $cvalue) {
						/*记录主键ID*/
						array_push($searchItem,$cvalue['id']);
					};

					if($num==1){
						$ids = $searchItem;
					}else{
						/*多个searchItem之间求交/并集*/
						if(isset($value['searchType'])&&$value['searchType']=="merge"){
							$ids = array_merge($ids,$searchItem);
						}else{
							$new = [];
							foreach($searchItem as $i_key => $i_value){
								if(in_array($i_value,$ids)){
									array_push($new,$i_value);
								};
							};
							$ids = $new;
						};
					};
				};
				$target = [];
				foreach($origin_data as $key_o => $value_o){
					if(in_array($value_o['id'],$ids)){
						if(!in_array($value_o[$value['key']],$target)){
							array_push($target,$value_o[$value['key']]);
						};
					};
				};
				$search = $target;

				if(!empty($search)){
					if(isset($newSearchItem[$value['middleKey']])){
						if(isset($value['type'])&&$value['type']=="merge"){
							$newSearchItem[$value['middleKey']] = [$value['condition'],array_merge($search,$newSearchItem[$value['middleKey']][1])];
						}else{
							$new = [];
							foreach($search as $s_key => $s_value){
								if(in_array($s_value,$newSearchItem[$value['middleKey']][1])){
									array_push($new,$s_value);
								};
							};
							$newSearchItem[$value['middleKey']] = [$value['condition'],$new];
						};
					}else{
						$newSearchItem[$value['middleKey']] = [$value['condition'],$search];
					}; 
				};
			};
			if(!empty($newSearchItem)){
				$data['searchItem'] = array_merge($data['searchItem'],$newSearchItem);
			}else{
				$data = [];
			};
		};

		return $data;

	}



	public static function CommonGetAfter($data,$res)
	{
		
		if(isset($data['getAfter'])){

			foreach ($res as $key => $value) {

				$copyValue = $value;
				foreach ($data['getAfter'] as $c_key => $c_value) {
					$new = [];

					if(is_array($c_value['middleKey'])){
						$finalItem = '';
						foreach ($c_value['middleKey'] as $cc_key => $cc_value) {
							if($cc_key==0){
								$finalItem = $copyValue[$c_value['middleKey'][0]];
							}else{
								if ($finalItem&&isset($finalItem[$c_value['middleKey'][$cc_key]])) {
									$finalItem = $finalItem[$c_value['middleKey'][$cc_key]];
								}else{
									$finalItem = '';
								};
							};
						};
						if($finalItem){
							$searchItem = [$c_value['condition'],$finalItem];
						};
					}else{
						$searchItem = [$c_value['condition'],$copyValue[$c_value['middleKey']]];
					};
					
					if(isset($c_value['info'])&&$searchItem){
						
						$c_value['searchItem'][$c_value['key']] = $searchItem;
						$modelData = [];
						$modelData['searchItem'] = $c_value['searchItem'];
						if(isset($c_value['order'])){
							$modelData['order'] = $c_value['order'];
						};
						$nRes = CommonModel::CommonGet($c_value['tableName'],$modelData);

						if(!empty($nRes['data'])){
							$nRes['data'][0] = resDeal($nRes['data'][0]);
							foreach ($c_value['info'] as $info_key => $info_value) {
							   $new[$info_value] = $nRes['data'][0][$info_value];
							};
						};
					}else if($searchItem){
						
						$c_value['searchItem'][$c_value['key']] = $searchItem;
						$modelData = [];
						$modelData['searchItem'] = $c_value['searchItem'];
						if(isset($c_value['order'])){
							$modelData['order'] = $c_value['order'];
						};
						$nRes = CommonModel::CommonGet($c_value['tableName'],$modelData);

						if(!empty($nRes['data'])){
							$new = resDeal($nRes['data']);
							if($c_value['tableName']=="Sku"){
								$new = self::checkStock($c_value['tableName'],[],$new);
							};
						};
					};
					
					if(isset($c_value['compute'])){
						foreach ($c_value['compute'] as $compute_key => $compute_value) {
							$compute_value[2][$c_value['key']] = $searchItem;

							$new[$compute_key] = CommonModel::CommonCompute($c_value['tableName'],$compute_value[0],$compute_value[1],$compute_value[2]);
						};
					};
					$res[$key][$c_key] = [];
					$res[$key][$c_key] = $new;
					$copyValue[$c_key] = $new;
				   
				};
				
			};
			
		};

		return $res;

	}



	public static function CommonSavePro($data)
	{
		
		if(isset($data['saveBefore'])){
			$newSearchItem = [];
			foreach ($data['saveBefore'] as $value) {

				$modelData = [];
				$modelData['searchItem'] = $value['searchItem'];
				$Res = CommonModel::CommonGet($value['tableName'],$modelData);

				if(!empty($nRes['data'])){
					$nRes['data'][0] = $nRes['data'][0]->toArray();
					foreach ($value['info'] as $info_key => $info_value) {
					   $data['data'][$info_key] = $nRes['data'][0][$info_value];
					};
				};
			};
		};
		return $data;

	}



	public static function CommonSaveAfter($table,$data)
	{
		
		if(isset($data['saveAfter'])){

			if(isset($data['searchItem']['id'])){
				$modelData = [];
				$modelData['searchItem'] = $data['searchItem'];
				$info = CommonModel::CommonGet($table,$modelData);

				if(count($info['data'])==0){
					throw new ErrorMessage([
						'msg' => '关联saveAfter失败',
					]);
				}else{
					$info = $info['data'][0];
				};
			};
			
			foreach ($data['saveAfter'] as $value) {

				$value = FuncService::check($value['tableName'],$value);

				if(isset($value['data']['res'])){
					foreach ($value['data']['res'] as $data_key => $data_value) {
						$value['data'][$data_key] = $info[$data_value];
					};
					unset($value['data']['res']);
				};
				
				if(isset($value['searchItem']['res'])){
					foreach ($value['searchItem']['res'] as $searchItem_key => $searchItem_value) {
						$value['searchItem'][$searchItem_key] = $info[$searchItem_value];
					};
					unset($value['searchItem']['res']); 
				};

				$res = CommonModel::CommonSave($value['tableName'],$value);
			};

		};
	}



	public static function getLimit($limit,$data)
	{

		if (isset($limit['getLimit'])&&is_array($limit['getLimit'])) {

			$limits = $limit['getLimit'];

			foreach ($data as $key => $value) {

				foreach ($limits as $c_value) {
					
					if (isset($value[$c_value])) {
	
						unset($data[$key][$c_value]);

					}

				}

			}
			
		}

		return $data;

	}



	public static function checkCoupon($dbTable,$data)
	{

		$coupons = CommonModel::CommonGet($dbTable,$data);
		
		foreach($coupons['data'] as $key => $value){
			
			if(isset($value['invalid_time'])&&($value['invalid_time']/1000<time())&&$value['use_step']==1){
				
				$modelData = [];
				$modelData['FuncName'] = 'update';
				$modelData['searchItem']['id'] = $value['id'];
				$modelData['data']['use_step'] = -1;
				$upCoupon = CommonModel::CommonSave('UserCoupon',$modelData);

			}

		}

	}



	/**
	 * @param dbTable表名
	 * @param data请求参数
	 * @param res查询结果数据
	 * @return 返回带有库存的数据
	 */
	public static function checkStock($dbTable,$data,$res)
	{
		/*获取单个商品一段时间内的每日库存情况*/
		if(isset($data['getOne'])&&isset($data['start_time'])&&isset($data['end_time'])){
			$product = $res[0];
			$start = date('Y-m-d',$data['start_time']); 
			$start = strtotime($start);
			$end = date('Y-m-d',$data['end_time']);
			$end = strtotime($end);
			$day = ($end-$start)/86400+1;
			for($i = 0; $i < $day; $i++){
				$modelData = [];
				$modelData['getOne'] = 'true';
				if($dbTable=='Product'){
					$modelData['searchItem']['product_no'] = $product['product_no'];
				}else if($dbTable=='Sku'){
					$modelData['searchItem']['sku_no'] = $product['sku_no'];
				};
				if($product['is_date']==1){
					$modelData['searchItem']['day_time'] = $start+86400*$i;
					$modelData['searchItem']['type'] = 2;
				}else{
					$modelData['searchItem']['type'] = 1;
				};
				$stock = CommonModel::CommonGet('ProductDate',$modelData);
				if(count($stock['data'])>0){
					if($product['is_date']==1){
						$res[0][$i]['day_time'] = $start+86400*$i;
						$res[0][$i]['stock'] = $stock['data'][0]['stock'];
						$res[0][$i]['group_stock'] = $stock['data'][0]['group_stock'];
					}else{
						$res[0]['stock'] = $stock['data'][0]['stock'];
						$res[0]['group_stock'] = $stock['data'][0]['group_stock'];
					}
				}else{
					/*库存不存在，新增*/
					$modelData = [];
					$modelData['FuncName'] = 'add';
					if($product['is_date']==1){
						$modelData['data']['type'] = 2;
						$modelData['data']['day_time'] = $start+86400*$i;
					}else{
						$modelData['data']['type'] = 1;
					};
					if($dbTable=='Product'){
						$modelData['data']['product_no'] = $product['product_no'];
					}else if($dbTable=='Sku'){
						$modelData['data']['sku_no'] = $product['sku_no'];
					};
					$modelData['data']['stock'] = $product['stock'];
					$modelData['data']['group_stock'] = $product['stock'];
					$modelData['data']['user_no'] = $product['user_no'];
					$modelData['data']['user_type'] = $product['user_type'];
					$modelData['data']['thirdapp_id'] = $product['thirdapp_id'];
					$addStock = CommonModel::CommonSave('ProductDate',$modelData);
					if($product['is_date']==1){
						$res[0][$i]['day_time'] = $start+86400*$i;
						$res[0][$i]['stock'] = $stock['data'][0]['stock'];
						$res[0][$i]['group_stock'] = $stock['data'][0]['group_stock'];
					}else{
						$res[0]['stock'] = $stock['data'][0]['stock'];
						$res[0]['group_stock'] = $stock['data'][0]['group_stock'];
					}
				};
			};
		}else{
			/*获取商品当日库存或标准库存*/
			foreach($res as $key => $value){
				$modelData = [];
				$modelData['getOne'] = 'true';
				if($dbTable=='Product'){
					$modelData['searchItem']['product_no'] = $value['product_no'];
				}else if($dbTable=='Sku'){
					$modelData['searchItem']['sku_no'] = $value['sku_no'];
				};

				$beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
				if($value['is_date']==1){
					$modelData['searchItem']['day_time'] = $beginToday;
					$modelData['searchItem']['type'] = 2;
				}else{
					$modelData['searchItem']['type'] = 1;
				};
				
				$stock = CommonModel::CommonGet('ProductDate',$modelData);
				if(count($stock['data'])>0){
					$res[$key]['stock'] = $stock['data'][0]['stock'];
					$res[$key]['group_stock'] = $stock['data'][0]['group_stock'];
				}else{
					/*库存不存在，新增*/
					$modelData = [];
					$modelData['FuncName'] = 'add';
					if($value['is_date']==1){
						$modelData['data']['type'] = 2;
						$modelData['data']['day_time'] = $beginToday;
					}else{
						$modelData['data']['type'] = 1;
						$modelData['data']['day_time'] = 0;
					};
					if($dbTable=='Product'){
						$modelData['data']['product_no'] = $value['product_no'];
					}else if($dbTable=='Sku'){
						$modelData['data']['sku_no'] = $value['sku_no'];
					};
					$modelData['data']['stock'] = $value['stock'];
					$modelData['data']['group_stock'] = $value['stock'];
					$addStock = CommonModel::CommonSave('ProductDate',$modelData);
					$res[$key]['stock'] = $value['stock'];
					$res[$key]['group_stock'] = $value['stock'];
				};
			};
		};
		return $res;

	}



	/**
	 * @param search是获取订单的条件
	 * @param type是类型，reduce库存减少，increase库存增加
	 */
	public static function dealStock($search,$type)
	{
		$orders = CommonModel::CommonGet('Order',$search);
		if(count($orders['data'])>0){
			
			foreach($orders['data'] as $key_o => $value_o){
				
				if($value_o['day_time']>0){
					$stock_type = 2;
				}else{
					$stock_type = 1;
				};

				/*购买sku*/
				if($value_o['sku_id']>0){
					$modelData = [];
					$modelData['getOne'] = 'true';
					$modelData['searchItem']['id'] = $value_o['sku_id'];
					$skuInfo = CommonModel::CommonGet('Sku',$modelData);
					if(count($skuInfo['data'])>0){
						$skuInfo = $skuInfo['data'][0];
						/*获取库存信息*/
						$modelData = [];
						$modelData['getOne'] = 'true';
						$modelData['searchItem']['day_time'] = $value_o['day_time'];
						$modelData['searchItem']['type'] = $stock_type;
						$modelData['searchItem']['sku_no'] = $skuInfo['sku_no'];
						$stock = CommonModel::CommonGet('ProductDate',$modelData);
						if(count($stock['data'])>0){
							$stock = $stock['data'][0];
							$modelData = [];
							$modelData['FuncName'] = 'update';
							$modelData['searchItem']['id'] = $skuInfo['id'];
							if($type=='reduce'){
								$modelData['data']['sale_count'] = $skuInfo['sale_count']+$value_o['count'];
							}else if($type=='increase'){
								$modelData['data']['sale_count'] = $skuInfo['sale_count']-$value_o['count'];
							};
							$upSku = CommonModel::CommonSave('Sku',$modelData);
							$modelData = [];
							$modelData['FuncName'] = 'update';
							$modelData['searchItem']['id'] = $stock['id'];
							if($type=='reduce'){
								if(empty($value_o['group_no'])){
									$modelData['data']['stock'] = $stock['stock']-$value_o['count'];
								}else{
									$modelData['data']['group_stock'] = $stock['group_stock']-$value_o['count'];
								};
							}else if($type=='increase'){
								if(empty($value_o['group_no'])){
									$modelData['data']['stock'] = $stock['stock']+$value_o['count'];
								}else{
									$modelData['data']['group_stock'] = $stock['group_stock']+$value_o['count'];
								};
							};
							$upStock = CommonModel::CommonSave('ProductDate',$modelData);
						};
					};
				};
				/*购买商品*/
				if($value_o['product_id']>0){
					$modelData = [];
					$modelData['getOne'] = 'true';
					$modelData['searchItem']['id'] = $value_o['product_id'];
					$productInfo = CommonModel::CommonGet('Product',$modelData);
					if(count($productInfo['data'])>0){
						$productInfo = $productInfo['data'][0];
						/*获取库存信息*/
						$modelData = [];
						$modelData['getOne'] = 'true';
						$modelData['searchItem']['day_time'] = $value_o['day_time'];
						$modelData['searchItem']['type'] = $stock_type;
						$modelData['searchItem']['product_no'] = $productInfo['product_no'];
						$stock = CommonModel::CommonGet('ProductDate',$modelData);
						if(count($stock['data'])>0){
							$stock = $stock['data'][0];
							$modelData = [];
							$modelData['FuncName'] = 'update';
							$modelData['searchItem']['id'] = $productInfo['id'];
							if($type=='reduce'){
								$modelData['data']['sale_count'] = $productInfo['sale_count']+$value_o['count'];
							}else if($type=='increase'){
								$modelData['data']['sale_count'] = $productInfo['sale_count']-$value_o['count'];
							};
							$upProduct = CommonModel::CommonSave('Product',$modelData);
							$modelData = [];
							$modelData['FuncName'] = 'update';
							$modelData['searchItem']['id'] = $stock['id'];
							if($type=='reduce'){
								if(empty($value_o['group_no'])){
									$modelData['data']['stock'] = $stock['stock']-$value_o['count'];
								}else{
									$modelData['data']['group_stock'] = $stock['group_stock']-$value_o['count'];
								};
							}else if($type=='increase'){
								if(empty($value_o['group_no'])){
									$modelData['data']['stock'] = $stock['stock']+$value_o['count'];
								}else{
									$modelData['data']['group_stock'] = $stock['group_stock']+$value_o['count'];
								};
							};
							$upStock = CommonModel::CommonSave('ProductDate',$modelData);
						};
					};
				};

			};
		};
	}
	
}