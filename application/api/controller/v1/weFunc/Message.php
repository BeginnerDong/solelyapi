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

use app\api\controller\v1\weFunc\Base as WxBase;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;	


class Message extends Controller{


	function __construct($data)
	{

	}	


	public static function sendMessage($post_data,$thirdapp_id)
	{

		$modelData = [];
        $modelData['searchItem'] = [
            'id'=>$thirdapp_id
        ];
        $thirdInfo = BeforeModel::CommonGet('ThirdApp',$modelData);

		if(count($thirdInfo['data'])>0){

			$thirdInfo = $thirdInfo['data'][0];

			if($thirdInfo['access_token']&&$thirdInfo['access_token_expire']>time()){

				$access_token = $thirdInfo['access_token'];

			}else{

				$thirdInfo['thirdapp_id'] = $thirdInfo['id'];
				$access_token = WxBase::getAccessToken($thirdInfo);

			}; 

		}else{

			throw new ErrorMessage([
	            'msg'=>'关联项目不存在',
	        ]);

		};

		// $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token;
		
		//订阅消息接口
		$url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=".$access_token;

		$res = WxBase::curl_wxpost($url,$post_data);

		return json_decode($res,true);
	}
}