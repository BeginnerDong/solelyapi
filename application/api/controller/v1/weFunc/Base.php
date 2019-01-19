<?php
/**
 * Created by 董博明.
 * Author: 董博明
 * Date: 2018/4/9
 * Time: 19:23
 */

namespace app\api\controller\v1\weFunc;
use think\Controller;
use think\Db;
use think\Request as Request;
use think\Loader;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Base extends Controller{


	function __construct($data)
	{

	}

	public static function getAccessToken($config,$outer=false)
	{

		$accessRes = curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config["appid"]."&secret=".$config["appsecret"]);

		if($accessRes){

			$accessRes = json_decode($accessRes,true);

			if (!$outer&&isset($config['thirdapp_id'])) {

				$modelData = [];
				$modelData['searchItem'] = [
		            'id'=>$config['thirdapp_id'],
		        ];
		        $modelData['data'] = array(
		            'access_token'=>$accessRes['access_token'],
		            'access_token_expire'=>time()+7000,
		        );
		        $modelData['FuncName'] = 'update';
		        $upThird = BeforeModel::CommonSave('ThirdApp',$modelData);

			}

			return $accessRes['access_token'];

		}else{

			return fasle;

		}
	}


	public static function curl_wxpost($url, array $params = array())
	{	
		//保护中文，微信api不支持中文转义的json结构
        array_walk_recursive($params, function (&$value) {
            $value = urlencode($value);
        });
        $data_string = urldecode(json_encode($params));
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	    curl_setopt(
	        $ch, CURLOPT_HTTPHEADER,
	        array(
	            'Content-Type: application/json'
	        )
	    );
	    $data = curl_exec($ch);
	    curl_close($ch);
	    return ($data);
	}
}