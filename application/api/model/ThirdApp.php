<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;

use app\api\model\User;

use app\lib\exception\ErrorMessage;

class ThirdApp extends BaseModel{


	public static function dealAdd($data)
	{
		$standard = [
			'appid'=>'',
			'appsecret'=>'',
			'app_description'=>'',
			'name'=>'',
			'codeName'=>'',
			'distribution_level'=>'',
			'distributionRule'=>'',
			'custom_rule'=>'',
			'phone'=>'',
			'mainImg'=>[],
			'smsKey_ali'=>'',
			'smsSecret_ali'=>'',
			'smsID_tencet'=>'',
			'smsKey_tencet'=>'',
			'scope'=>'',
			'scope_description'=>'',
			'app_type'=>'',
			'mchid'=>'',
			'wxkey'=>'',
			'wx_token'=>'',
			'wxgh_id'=>'',
			'wx_appid'=>'',
			'wx_appsecret'=>'',
			'encodingaeskey'=>'',
			'access_token'=>'',
			'access_token_expire'=>'',
			'aestype'=>'',
			'picstandard'=>'',
			'picstorage'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'invalid_time'=>'',
			'view_count'=>'',
			'status'=>1,
			'parentid'=>1,
			'child_array'=>[],
			'user_no'=>'',
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

		if(isset($data['data'])&&isset($data['data']['status'])){
			$ThirdApp = (new ThirdApp())->where($data['searchItem'])->select();
			foreach ($ThirdApp as $key => $value) {
				$relationRes = (new User())->save(
					['status'  => $data['data']['status']],
					['thirdapp_id' => $ThirdApp[$key]['id']]
				);
			};
		};
		return $data;
		
	}


	public static function dealRealDelete($data)
	{
		return $data;
	}


	public static function ThirdAppGet($data)
	{

		$model = new ThirdApp;
		$sqlStr = preModelStr($data);
		$sqlStr = $sqlStr."->select();";
		$res = eval($sqlStr);
		return $res->toArray();
		
	}

	//物理删除
	public static function TruedelTuser($data){
		$thirdApp=Db::table('third_app')->where('id',$data['id'])->delete();
		$admin=Db::table('admin')->where('thirdapp_id',$data['id'])->delete();
		$article=Db::table('article')->where('thirdapp_id',$data['id'])->delete();
		$articleContent=Db::table('article_content')->where('thirdapp_id',$data['id'])->delete();
		$category=Db::table('category')->where('thirdapp_id',$data['id'])->delete();
		$product=Db::table('product')->where('thirdapp_id',$data['id'])->delete();
		$user=Db::table('user')->where('thirdapp_id',$data['id'])->delete();
		$userAddress=Db::table('user_address')->where('thirdapp_id',$data['id'])->delete();
		if($thirdApp||$admin||$article||$articleContent||$category||$product||$user||$userAddress){
			return 1;
		}else{
			return 0;
		}
	}

	//访问量记录
	public static function addViewCount($id)
	{
		$viewadd = Db::table('third_app')->where('id',$id)->setInc('view_count');
		return $viewadd;
	}
}
