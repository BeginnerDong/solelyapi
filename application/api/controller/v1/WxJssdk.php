<?php 
/**
 * Created by wjm.
 * User: wjm
 * Date: 2018-05-02
 * Time: 10:36
 */

namespace app\api\controller\v1;

use think\Controller;
use think\Db;
use think\Request as Request;
use think\Loader;
use app\api\service\Token as TokenService;
use app\api\model\Common as CommonModel;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;
use think\Cache;


class WxJssdk 
{
	

	public function __construct() {
		
	}

	public function getSignPackage() {

		$param = Request::instance()->param();
    	$this->thirdapp_id = $param['thirdapp_id'];
    	$config = $this->getThirdConfig();

		$jsapiTicket = $this->getJsApiTicket($config);
		//$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url = $param['url'];
		
		$timestamp = time();
		$nonceStr = $this->createNonceStr();
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
		$signature = sha1($string);
		$signPackage = array(
		  "appId"     => $config['appid'],
		  "nonceStr"  => $nonceStr,
		  "timestamp" => $timestamp,
		  "url"       => $url,
		  "signature" => $signature,
		  "rawString" => $string
		);
		return $signPackage; 
	}



	public function getThirdConfig(){
		
		$modelData = [];
        $modelData['searchItem']['id'] = $this->thirdapp_id;
        $thirdRes=CommonModel::CommonGet('ThirdApp',$modelData);
		if(count($thirdRes['data'])>0){
			
			$config['appid'] = $thirdRes['data'][0]['wx_appid'];
			$config['appsecret'] = $thirdRes['data'][0]['wx_appsecret'];
			$config['encodingaeskey'] = $thirdRes['data'][0]['encodingaeskey'];
			$config['access_token'] = $thirdRes['data'][0]['access_token'];
			$config['access_token_expire'] = $thirdRes['data'][0]['access_token_expire'];
			return $config;
		}else{
			return false;
		}
	}

	private function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
		  $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		};
		return $str;
	}

	private function getJsApiTicket($config){

		// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
		
		
		if (!Cache::get('jsapi_ticket')) {
		  if($config['access_token']&&$config['access_token_expire']>time()){
		  	$access_token = $config['access_token'];
		  }else{
		  	$access_token = $this->getAccessToken($config);
		  };

		  
		  $res = curl_get("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi");
		  
		  $res = json_decode($res,true);
		  
		 
		  $ticket = $res['ticket'];
		  if ($ticket) {
		    $ticket = Cache::set('jsapi_ticket',$ticket,7000);
		    return $ticket;
		  }
		} else {
			return Cache::get('jsapi_ticket');
		};
	}

	public function getAccessToken($config){
		
		$accessRes = curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config["appid"]."&secret=".$config["appsecret"]);
		
		if($accessRes){
			$accessRes = json_decode($accessRes,true);
			
			$modelData = [];
	        $modelData['searchItem']['id'] = $this->thirdapp_id;
	        $modelData['data']['access_token'] = $accessRes['access_token'];
	        $modelData['data']['access_token_expire'] = time()+7000;
	        $modelData['FuncName'] = 'update';
	        $res=CommonModel::CommonSave('ThirdApp',$modelData);
			return $accessRes['access_token'];
		}else{
			return fasle;
		}
	}

	private function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_URL, $url);
		$res = curl_exec($curl);
		curl_close($curl);
		return $res;
	}

	
}
?>