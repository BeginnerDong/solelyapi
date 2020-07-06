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


class User {


	public static function deal($data)
	{

		if (isset($data['data']['login_name'])) {

			$modelData = [];
			$modelData['searchItem']['login_name'] = $data['data']['login_name'];
			$modelData['searchItem']['status'] = ['in',[-1,0,1]];
			$user = CommonModel::CommonGet('User',$modelData);
			if (count($user['data'])>0) {
				throw new ErrorMessage([
					'msg' => '用户名已存在',
				]);
			};

		};
		
		if(isset($data['data']['nickname'])){

			$data['data']['nickname'] = self::removeEmojiChar($data['data']['nickname']);

		};

        return $data;

	}
	
	
	public static function removeEmojiChar($str)
	{
		$mbLen = mb_strlen($str);
		
		$strArr = [];
		for ($i = 0; $i < $mbLen; $i++) {
			$mbSubstr = mb_substr($str, $i, 1, 'utf-8');
			if (strlen($mbSubstr) >= 4) {
				continue;
			}
			$strArr[] = $mbSubstr;
		}
		
		return implode('', $strArr);
	}

}