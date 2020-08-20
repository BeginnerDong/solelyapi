<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class UserInfo {


	public static function deal($data)
	{

		if(isset($data['data']['phone'])){
				
			if(preg_match("/^1[34578]\d{9}$/",$data['data']['phone'])){
				//手机号格式正确
			}else{
				throw new ErrorMessage([
					'msg' => '手机号格式错误',
				]);
			};

		};

		return $data;

	}

}