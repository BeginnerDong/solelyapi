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


class Label {


	public static function deal($data)
	{

		if ($data['FuncName']=='update'&&isset($data['data']['status'])&&$data['data']['status']==-1) {

			$label = CommonModel::CommonGet('Label',$data);
			if(count($label['data'])==0){
				throw new ErrorMessage([
					'msg' => '菜单不存在',
				]);
			};
			$label = $label['data'][0];
			if($label['type']==1){
				/*关联删除文章*/
				$modelData = [];
				$modelData['searchItem']['menu_id'] = $label['id'];
				$article = CommonModel::CommonGet('Article',$modelData);
				if(count($article['data'])>0){
					$modelData['FuncName'] = 'update';
					$modelData['data']['status'] = -1;
					$delArticle = CommonModel::CommonSave('Article',$modelData);
				};
			};
			if($label['type']==3){
				/*关联删除商品*/
				$modelData = [];
				$modelData['searchItem']['category_id'] = $label['id'];
				$product = CommonModel::CommonGet('Product',$modelData);
				if(count($product['data'])>0){
					$modelData['FuncName'] = 'update';
					$modelData['data']['status'] = -1;
					$delArticle = CommonModel::CommonSave('Product',$modelData);
				};
			};
		};

		return $data;

	}   

}