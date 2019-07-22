<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\User as UserService;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Func {


	public static function check($dbTable,$data)
	{

		if ($dbTable=="User") {
		
			$data = UserService::deal($data);
		
		};

        return $data;

	}   

}