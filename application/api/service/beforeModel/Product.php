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


class Product {


	public static function deal($data)
	{

		if ($data['FuncName']=='update'&&isset($data['data']['stock'])) {

			$product = CommonModel::CommonGet('Product',$data);
			if (count($product['data'])==0) {
				throw new ErrorMessage([
					'msg' => '商品不存在',
				]);
			};
			$product = $product['data'][0];
			if($product['is_date']==0){
				/*更新标准库存*/
				$modelData = [];
				$modelData['getOne'] = 'true';
				$modelData['searchItem']['product_no'] = $product['product_no'];
				$modelData['searchItem']['type'] = 1;
				$stock = CommonModel::CommonGet('ProductDate',$modelData);
				if(count($stock['data'])>0){
					$stock = $stock['data'][0];
					$modelData = [];
					$modelData['FuncName'] = 'update';
					$modelData['searchItem']['id'] = $stock['id'];
					$modelData['data']['stock'] = $data['data']['stock'];
					$upStock = CommonModel::CommonSave('ProductDate',$modelData);
				};
			};
		};

		return $data;

	}   

}