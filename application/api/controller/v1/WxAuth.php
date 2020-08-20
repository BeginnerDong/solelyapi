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

use app\api\model\Distribution;

use app\api\service\Token as TokenService;
use app\api\service\beforeModel\Common as BeforeModel;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;
use think\Cache;

class WxAuth 
{



	public function index()
	{
		
		$param = Request::instance()->param();
		$this->thirdapp_id = $param['thirdapp_id'];
		if($param['code']){
			$this->code = $param['code'];
			$ThirdInfo = $this->getThirdConfig();
			$config = [];
			$config['token'] = $ThirdInfo['wx_token'];
			$config['appid'] = $ThirdInfo['wx_appid'];
			$config['appsecret'] = $ThirdInfo['wx_appsecret'];
			$config['encodingaeskey'] = $ThirdInfo['encodingaeskey'];
			$config['access_token'] = $ThirdInfo['access_token'];
			$config['access_token_expire'] = $ThirdInfo['access_token_expire'];
			$this->config = $config;
			$param['distribution_level'] = $ThirdInfo['distribution_level'];
			
			if($this->config){

				$this->access_token = $this->getOpenId();
				if(isset($this->access_token['errcode'])){
					throw new ErrorMessage([
						'msg'=>$this->access_token['errmsg'],
						'solelyCode'=>300000,
					]);
				}else{
					$wxResult['openid'] = $this->access_token['openid'];
				};
				return $this->grantToken($wxResult,$param);
			}else{
				return 'no_config';
			}
		}else{	
			return 'no_code';
		}
	}



	public function grantToken($wxResult,$data)
	{
		
		$openid = $wxResult['openid'];

		$modelData = [];
		$modelData['searchItem']['openid'] = $openid;
		$modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
		$modelData['searchItem']['status'] = 1;
		$modelData['getOne'] = "true";
		$user = BeforeModel::CommonGet('User',$modelData);

		if(isset($wxResult['unionid'])){ 
	
			$modelData = [];
			$modelData['searchItem']['unionid'] = $wxResult['unionid'];
			$unionUser = BeforeModel::CommonGet('User',$modelData);
		};

		$userInfo = $this->getUserInfo();
		//注意，微信接口返回的headimgurl是小写
		
		if(count($user['data'])>0){
			
			$uid = $user['data'][0]['id'];
			$modelData = [];
			$modelData['FuncName'] = 'update';
			$modelData['searchItem'] = ['id'=>$uid];
			if(isset($userInfo['nickname'])&&!empty($userInfo['nickname'])){
				$modelData['data']['nickname'] = $userInfo['nickname'];
			};
			if(isset($userInfo['headimgurl'])&&!empty($userInfo['headimgurl'])){
				$modelData['data']['headImgUrl'] = $userInfo['headimgurl'];
			};
			$res = BeforeModel::CommonSave('User',$modelData);

		}else{

			$modelData = [];
			if(isset($data['data'])){
				$modelData['data'] = $data['data'];
			};
			$modelData['data']['nickname'] = (isset($userInfo['nickname'])&&!empty($userInfo['nickname']))?$userInfo['nickname']:'';
			$modelData['data']['headImgUrl'] = (isset($userInfo['headimgurl'])&&!empty($userInfo['headimgurl']))?$userInfo['headimgurl']:'';
			$modelData['data']['openid'] = $openid;
			$modelData['data']['user_type'] = 0;
			$modelData['data']['thirdapp_id'] = $data['thirdapp_id'];
			$modelData['FuncName'] = 'add';
			if(isset($wxResult['unionid'])){
				$modelData['data']['unionid'] = $wxResult['unionid'];
			};
			if(isset($unionUser)&&count($unionUser['data'])>0){
				$modelData['data']['user_no'] = $unionUser[0]['user_no'];
			}else{
				$modelData['data']['user_no'] = makeUserNo();  
				$newUserNo = $modelData['data']['user_no'];
			};
			if(isset($data['parent_no'])){
				$modelData['data']['parent_no'] = $data['parent_no'];
			};
			if(isset($data['parent_no'])&&isset($data['parent_info'])){
				$parentInfo = User::get(['user_no' => $data['parent_no']]);
				foreach ($data['parent_info'] as $value) {
					$modelData['data'][$value] = $parentInfo[$value];
				};
			};
			$modelData['saveAfter'] = [
				[
					'tableName'=>'UserInfo',
					'FuncName'=>'add',
					'data'=>[
						'user_no'=>$modelData['data']['user_no'],
						'thirdapp_id'=>$data['thirdapp_id']
					]
				]
			];

			$uid = BeforeModel::CommonSave('User',$modelData);
			if(!$uid>0){
				throw new ErrorMessage([
					'msg'=>'新添加用户失败'
				]);
			};
			if($data['distribution_level']>0&&isset($data['parent_no'])){

				$modelData = [];
				$modelData['data']['level'] = 1;
				$modelData['data']['parent_no'] = $data['parent_no'];
				$modelData['data']['child_no'] = $newUserNo;
				$modelData['data']['thirdapp_id'] = $data['thirdapp_id'];
				$modelData['FuncName'] = 'add';
				
				$distriRes = BeforeModel::CommonSave('Distribution',$modelData);
				
				$parent_no = $data['parent_no'];
				if($data['distribution_level']>0){
					for ($i=0; $i < $data['distribution_level']-1; $i++) { 
						$res = (new Distribution())->where('child_no','=',$parent_no)->find();
						
						if($res){
							$content = [];
							$content['parent_no'] = $res['parent_no'];
							$content['child_no'] = $newUserNo;
							$content['level'] = $res['level']+1;
							$content['thirdapp_id'] = $data['thirdapp_id'];
							$content['status'] = 1;
							$content['create_time'] = time();
							$distriRes = (new Distribution())->allowField(true)->save($content);
							$parent_no = $res['parent_no'];
						}else{
							break;
						};
					};
				};
			};

		};


		$modelData = [];
		$modelData['searchItem']['id'] = $uid;
		$modelData['getOne'] = "true";
		$user = BeforeModel::CommonGet('User',$modelData);
		$modelData = [];
		$modelData['searchItem']['id'] = $user['data'][0]['thirdapp_id'];
		$modelData['getOne'] = "true";
		$thirdApp = BeforeModel::CommonGet('ThirdApp',$modelData);
		
		$user['data'][0]['thirdApp'] = $thirdApp['data'][0];

		$userNo = $user['data'][0]['user_no'];

		$token = generateToken();
		$cacheResult = Cache::set($token,$user['data'][0],3600);
		if (!$cacheResult){
			throw new ErrorMessage([
				'msg' => '服务器缓存异常',
				'errorCode' => 10005
			]);
		};

		throw new SuccessMessage([
			'msg'=>'登录成功',
			'token'=>$token,
			'info'=>$user['data'][0]
		]);

	}



	public function getCode()  
	{  
		if (isset($_GET["code"])) {  
			return $_GET["code"];  
		} else {  
			$str = "location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appid . "&redirect_uri=" . $this->index_url . "&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";  
			header($str);  
			exit;  
		}  
	}



	public function getOpenId()  
	{  
		$access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->config['appid'] . "&secret=" . $this->config['appsecret'] . "&code=" . $this->code . "&grant_type=authorization_code";  
		$access_token_json = $this->https_request($access_token_url);  
		$access_token_array = json_decode($access_token_json, TRUE);  
		return $access_token_array;  
	}



	public function getThirdConfig()
	{

		$modelData = [];
		$modelData['searchItem']['id'] = $this->thirdapp_id;
		$modelData['getOne'] = "true";
		$thirdRes = BeforeModel::CommonGet('ThirdApp',$modelData);
		if(count($thirdRes['data'])>0){
			$thirdRes = $thirdRes['data'][0];
			return $thirdRes;
		}else{
			return false;
		}
	}



	public function getUserInfo(){

		$config = $this->getThirdConfig();
		
		if($config['access_token']&&$config['access_token_expire']>time()){
			$ACCESS_TOKEN = $config['access_token'];
		}else{
			$ACCESS_TOKEN = self::getAccessToken($config);
		};

		$str = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$ACCESS_TOKEN."&openid=".$this->openid;
		$userInfoRes = curl_get($str);
		if($userInfoRes){
			$userInfo = json_decode($userInfoRes,true);
			return $userInfo;
		}else{
			return false;
		};

	}
	
	

	private static function getAccessToken($config)
	{

		$accessRes = curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config["wx_appid"]."&secret=".$config["wx_appsecret"]);
		if($accessRes){
			$accessRes = json_decode($accessRes,true);
			$modelData = [];
			$modelData['FuncName'] = 'update';
			$modelData['searchItem']['wx_appid'] = $config['wx_appid'];
			$modelData['searchItem']['wx_appsecret'] = $config['wx_appsecret'];
			$modelData['data']['access_token'] = $accessRes['access_token'];
			$modelData['data']['access_token_expire'] = time()+7000;
			$updateThirdApp = BeforeModel::CommonSave('ThirdApp',$modelData);
			return $accessRes['access_token'];
		}else{
			throw new ErrorMessage([
				'msg'=>'获取AccessToken失败',
			]); 
		}

	}



	public function updateThirdApp($thirdInfo)
	{
		
		$sqlString = 'update third_app set ';
		foreach ($thirdInfo as $key => $value) {  
			if($key!="id"){
				$sqlString .= $key." =' ".$value."',";
			};
		};
		$sqlString = rtrim($sqlString, ',');
		$sqlString .= " where id =".$this->thirdapp_id;
		//return $sqlString;
		$updateThirdAppRes = Db::execute($sqlString);
		if($updateThirdAppRes){
			return $updateThirdAppRes;
		}else{
			return false;
		}
	}



	public function https_request($url, $data = null)  
	{
		$curl = curl_init();  
		curl_setopt($curl, CURLOPT_URL, $url);  
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);  
		if (!empty($data)) {  
			curl_setopt($curl, CURLOPT_POST, 1);  
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
		}  
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
		$output = curl_exec($curl);  
		curl_close($curl);  
		return $output;  
	}



	public function getToken($id)
	{
		$info['uid'] = $id;
		$tokenservice = new TokenService();
		$key = $tokenservice::generateToken();
		$value = json_encode($info);
		$expire_in = config('setting.token_expire_in');
		$result = cache($key, $value, $expire_in);
		if (!$result){
			throw new TokenException([
				'msg' => '服务器缓存异常',
				'errorCode' => 10005
			]);
		}
		return $key;
	}
}
?>