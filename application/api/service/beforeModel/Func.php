<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\User as UserService;
use app\api\service\beforeModel\Product as ProductService;
use app\api\service\beforeModel\Sku as SkuService;
use app\api\service\beforeModel\UserInfo as UserInfoService;
use app\api\service\beforeModel\Order as OrderService;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Func {


	public static function check($dbTable,$data)
	{

		if ($dbTable=="User") {
		
			$data = UserService::deal($data);
		
		};
		
		if ($dbTable=="Product") {
		
			$data = ProductService::deal($data);
		
		};
		
		if ($dbTable=="Sku") {
		
			$data = SkuService::deal($data);
		
		};
		
		if ($dbTable=="UserInfo") {
		
			$data = UserInfoService::deal($data);
		
		};
		
		if ($dbTable=="Order") {
		
			$data = OrderService::deal($data);
		
		};

		return $data;

	}

}