<?php
namespace app\api\model;
use think\Model;
use think\Loader;
use think\Db;
use think\Cache;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

use app\api\model\Article;
use app\api\model\File;
use app\api\model\Label;
use app\api\model\Log;
use app\api\model\Message;
use app\api\model\Relation;
use app\api\model\ThirdApp;
use app\api\model\User;
use app\api\model\UserInfo;
use app\api\model\Auth;
use app\api\model\VisitorLogs;
use app\api\model\Distribution;
use app\api\model\FlowLog;
use app\api\model\UserAddress;
use app\api\model\Role;
use app\api\model\Order;
use app\api\model\OrderItem;
use app\api\model\Product;
use app\api\model\Sku;
use app\api\model\WxFormId;
use app\api\model\Coupon;
use app\api\model\UserCoupon;
use app\api\model\CouponRelation;
use app\api\model\PayLog;
use app\api\model\WxTemplate;
use app\api\model\ProductDate;


class Common extends Model{



	public static function CommonGet($dbTable,$data)
	{
		
		$model = self::loaderModel($dbTable);
		$sqlStr = preModelStr($data);
		if(!isset($data['searchItem']['status'])){
			$data['searchItem']['status'] = 1;
		};

		if(isset($data['order']['distance'])&&isset($data['order']['longitude'])&&isset($data['order']['latitude'])){
			$order = $data['order']['distance'];
			$longitude = $data['order']['longitude'];
			$latitude = $data['order']['latitude'];
			$data['order'] = [];
			$item = "ACOS(SIN((".$latitude." * 3.1415) / 180 ) *SIN(( latitude * 3.1415) / 180 ) +COS((".$latitude." * 3.1415) / 180 ) * COS(( latitude * 3.1415) / 180 ) *COS((".$longitude." * 3.1415) / 180 - ( longitude * 3.1415) / 180 ) ) * 6380 ";
			$data['order'][$item] = $order;
		};
		
		if (isset($data['compute'])) {
			$new = [];
			foreach ($data['compute'] as $compute_key => $compute_value) {
				if(isset($compute_value[2])&&!empty($compute_value[2])){
					$new[$compute_key] = self::CommonCompute($dbTable,$compute_value[0],$compute_value[1],$compute_value[2]);
				}else{
					$new[$compute_key] = self::CommonCompute($dbTable,$compute_value[0],$compute_value[1],$data['searchItem']);
				};
			};
		};	
		if ($dbTable=='Distribution'&&isset($data['searchItem'])&&isset($data['searchItem']['user_no'])) {
			unset($data['searchItem']['user_no']);
		};
		if ($dbTable=='Distribution'&&isset($data['searchItem'])&&isset($data['searchItem']['user_type'])) {
			unset($data['searchItem']['user_type']);
		};

		if(isset($data['paginate'])){  

			$pagesize = $data['paginate']['pagesize'];
			$paginate = $data['paginate'];
			$paginate['page'] = $data['paginate']['currentPage'];
			$sqlStr = $sqlStr."paginate(\$pagesize,false,\$paginate);";
			
			$res = eval($sqlStr);
			$res = $res->toArray();

			$final = [
				'total'=>$res['total'],
			];

			$res = $model->dealGet(resDeal($res['data']));
			
		}else if(isset($data['getOne'])||(isset($data['searchItem']['id'])&&!is_array($data['searchItem']['id']))){
			
			$sqlStr = $sqlStr."find();";
			$find = eval($sqlStr);
			if($find){
				$res[0] = $find;
				$res = $model->dealGet(resDeal($res));
			}else{
				$res = [];
			}
			
		}else{
			
			//获取SKU时，屏蔽掉删除的选项
			if($dbTable=='Label'&&isset($data['searchItemOr'])){
				$map = $data['searchItem'];
				$or_map = $data['searchItemOr'];
				$res = Db::name('label')->where(function ($query) use ($map) {
									$query->where($map);
								})->whereOr(function ($query) use ($or_map) {
									$query->where($or_map);
								})->select();
				$res = resDeal($res);
			}else{
				$sqlStr = $sqlStr."select();";
				$res = eval($sqlStr);
				$res = $model->dealGet(resDeal($res));
				if($dbTable=='article'){
					$updateData = [];
					foreach ($res as $key => $value) {
						array_push($updateData,['id'=>$value['id'],'view_count'=>$value['view_count']+1]);
					};
					$model->saveAll($updateData);
				};
			};

		};
		
		if (isset($data['compute'])) {
			$final['compute'] = $new;
		};
		
		/*过滤字段*/
		if(isset($data['info'])&&count($res)>0){
			$new = [];
			foreach($res as $res_key => $res_value){
				foreach ($data['info'] as $info_key => $info_value) {
				   $new[$res_key][$info_value] = $res_value[$info_value];
				};
			};
			$res = $new;
		};

		$final['data'] = $res;
		return $final;
		
	}



	public static function CommonSave($dbTable,$data)
	{
		
		$model = self::loaderModel($dbTable);

		$sqlStr = preModelStr($data);
		if (isset($data['data'])) {
			$data['data'] = keepNum($data['data']);
		};
		
		if(isset($data['data']['password'])){
			$data['data']['password'] = md5($data['data']['password']);
		};
		
		
		$FuncName = $data['FuncName'];
		Db::startTrans();
		try{

			if($FuncName=='update'){

				$data['data']['update_time'] = time();
				$model->dealUpdate($data);
				$data['data'] = jsonDeal($data['data']);
				$sqlStr = $sqlStr."update(\$data[\"data\"]);";
				$finalRes = eval($sqlStr);

			}else{
				if(isset($data['dataArray'])){
					$finalRes = [];
					foreach ($data['dataArray'] as $key => $value) {
						$modelData = [];
						$modelData['data'] = $value;
						$modelData = $model->dealAdd($modelData);
						$res = $model->isUpdate(false)->data($modelData['data'], true)->allowField(true)->save();
						$finalRes['第'.($key+1).'条'] = $model->id;
					};
					
				}else{

					$data = $model->dealAdd($data);

					$data['data'] = jsonDeal($data['data']);
					
					$res = $model->allowField(true)->save($data['data']);
					
					$finalRes = $model->id;
				};
			};

			Db::commit(); 
			return $finalRes;

		} catch (\Exception $e) {
			// 回滚事务
			
			if(isset($e->msg)){
				throw new ErrorMessage([
					'msg' => $e->msg,
				]);
			}else{
				var_dump($e);
			};
			
			Db::rollback();
		};
	}



	public static function CommonDelete($dbTable,$data)
	{
		$model = self::loaderModel($dbTable);
		$sqlStr = preModelStr($data);
		$sqlStr = $sqlStr."delete();";
		Db::startTrans();
		try{
			$res = eval($sqlStr);
			// $model->realDeleteData($data);
			Db::commit(); 
			return $res;
		} catch (\Exception $e) {
			// 回滚事务
			if(isset($e->msg)){
				throw new ErrorMessage([
					'msg' => $e->msg,
				]);
			}else{
				var_dump($e);
			};
			Db::rollback();
		};
		
	}



	public static function CommonCompute($model,$method,$key,$map)
	{
		
		$model =self::loaderModel($model);

		if ($method!='count') {
			
			$num = $model->where($map)->$method($key); 

		}else{

			$num = $model->where($map)->count();

		}

		return $num;
	}



	public static function loaderModel($dbTable)
	{

		if($dbTable=='Article'){
			return new Article;
		}else if($dbTable=='Label'){
			return new Label;
		}else if($dbTable=='Log'){
			return new Log;
		}else if($dbTable=='Message'){
			return new Message;
		}else if($dbTable=='Relation'){
			return new Relation;
		}else if($dbTable=='ThirdApp'){
			return new ThirdApp;
		}else if($dbTable=='User'){
			return new User;
		}else if($dbTable=='UserInfo'){
			return new UserInfo;
		}else if($dbTable=='Auth'){
			return new Auth;
		}else if($dbTable=='VisitorLogs'){
			return new VisitorLogs;
		}else if($dbTable=='Distribution'){
			return new Distribution;
		}else if($dbTable=='FlowLog'){
			return new FlowLog;
		}else if($dbTable=='UserAddress'){
			return new UserAddress;
		}else if($dbTable=='Role'){
			return new Role;
		}else if($dbTable=='File'){
			return new File;
		}else if($dbTable=='Order'){
			return new Order;
		}else if($dbTable=='OrderItem'){
			return new OrderItem;
		}else if($dbTable=='Product'){
			return new Product;
		}else if($dbTable=='Sku'){
			return new Sku;
		}else if($dbTable=='WxFormId'){
			return new WxFormId;
		}else if($dbTable=='Coupon'){
			return new Coupon;
		}else if($dbTable=='UserCoupon'){
			return new UserCoupon;
		}else if($dbTable=='CouponRelation'){
			return new CouponRelation;
		}else if($dbTable=='PayLog'){
			return new PayLog;
		}else if($dbTable=='WxTemplate'){
			return new WxTemplate;
		}else if($dbTable=='ProductDate'){
			return new ProductDate;
		}else{
			throw new ErrorMessage([
				'msg' => 'tableName有误',
			]);
		};
	}



	public static function imgManage($dbTable,$data)
	{
		
		if(isset($data['data']['mainImg'])||isset($data['data']['bannerImg'])||isset($data['data']['content'])){
			if($data['FuncName']=="update"){
				//获取关联信息
				$dataImg = [];
				$model = Loader::model($dbTable);
				$sqlStr = preModelStr($data);
				$sqlStr = $sqlStr."select();";
				$info = eval($sqlStr);				
				$info = $model->dealGet(resDeal($info));
				
				if (count($info)==0) {
					throw new ErrorMessage([
						'msg' => '更新图片无法找到id',
					]);
				};
				foreach ($info as $value) {
					if(isset($value['mainImg'])){
						foreach ($value['mainImg'] as $c_value) {
							array_push($dataImg,$c_value['url']);
						};
					};
					if(isset($value['bannerImg'])){
						foreach ($value['bannerImg'] as $c_value) {
							array_push($dataImg,$c_value['url']);
						};
					};
					if(isset($value['content'])){
						$dataImg = array_merge($dataImg,takeImgList($value['content'])[1]);
					};
					$list = Relation::all([
						'relation_one'=>$value['id'],
						'relation_one_table'=>$dbTable,
						'relation_two_table'=>'File',
					]);
					$deleteImg = [];
					foreach($list as $c_value){
						if(!in_array($c_value['relation_two'], $dataImg)){
							array_push($deleteImg,$c_value['relation_two']);
						}else{
							$index = array_search($c_value['relation_two'],$dataImg);
							array_splice($dataImg, $index, 1);
						};
					};
					if(count($deleteImg)>0){
						$res = Relation::where([
							'relation_one'=>$value['id'],
							'relation_two'=>['in',$deleteImg],
							'relation_one_table'=>$dbTable,
							'relation_two_table'=>'File',
						])->delete();
						if (!$res) {
							throw new ErrorMessage([
								'msg' => '删除图片relation失败',
							]);
						};
					};
					if(count($dataImg)>0){
						$addData = [];
						foreach($dataImg as $c_value){
							array_push($addData,[
								'relation_one'=>$value['id'],
								'relation_two'=>$c_value,
								'relation_one_table'=>$dbTable,
								'relation_two_table'=>'File',
								'thirdapp_id'=>$value['thirdapp_id'],
								'status'=>1,
								'create_time'=>time(),
								'update_time'=>time(),
							]);
						};
						$relation = new Relation;
						$res = $relation->saveAll($addData, false);
						if (!$res) {
							throw new ErrorMessage([
								'msg' => '新增图片relation失败',
							]);
						};
					}
				};
			};
			
			if($data['FuncName']=="add"){
				$dataImg = [];
				if(isset($data['data']['mainImg'])){
					foreach ($data['data']['mainImg'] as $c_value) {
						array_push($dataImg,$c_value['url']);
					};
				};
				if(isset($data['data']['bannerImg'])){
					foreach ($data['data']['bannerImg'] as $c_value) {
						array_push($dataImg,$c_value['url']);
					};
				};
				if(isset($data['data']['content'])){
					$dataImg = array_merge($dataImg,takeImgList($data['data']['content'])[1]);
				};
				if(count($dataImg)>0){
					$addData = [];
					foreach($dataImg as $c_value){
						array_push($addData,[
							'relation_one'=>$data['searchItem']['id'],
							'relation_two'=>$c_value,
							'relation_one_table'=>$dbTable,
							'relation_two_table'=>'File',
							'thirdapp_id'=>$data['data']['thirdapp_id'],
							'status'=>1,
							'create_time'=>time(),
							'update_time'=>time(),
						]);
					};
					$relation = new Relation;
					$res = $relation->saveAll($addData, false);
					if (!$res) {
						throw new ErrorMessage([
							'msg' => '新增图片relation失败',
						]);
					};
				}
			}
		}
	}

}