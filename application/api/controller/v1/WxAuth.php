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
            $ThirdAppInfo = $this->getThirdConfig();
            $config = [];
            $config['token'] = $ThirdAppInfo['wx_token'];
            $config['appid'] = $ThirdAppInfo['wx_appid'];
            $config['appsecret'] = $ThirdAppInfo['wx_appsecret'];
            $config['encodingaeskey'] = $ThirdAppInfo['encodingaeskey'];
            $config['access_token'] = $ThirdAppInfo['access_token'];
            $config['access_token_expire'] = $ThirdAppInfo['access_token_expire'];
            $this->config = $config;
            $param['distribution_level'] = $ThirdAppInfo['distribution_level'];
	    	
	    	if($this->config){

	    		$this->access_token = $this->getOpenId();
                if(isset($this->access_token['errcode'])){
                    throw new ErrorMessage([
                        'msg'=>$this->access_token['errmsg'],
                        'solelyCode'=>201000,
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

    public function grantToken($wxResult,$data){
        
        $openid = $wxResult['openid'];

        $modelData = [];
        $modelData['searchItem']['openid'] = $openid;
        $modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
        $modelData['searchItem']['status'] = 1;
        $user=BeforeModel::CommonGet('User',$modelData);

        if(isset($wxResult['unionid'])){ 
            $modelData = [];
            $modelData['searchItem']['unionid'] = $wxResult['unionid'];
            $unionUser=BeforeModel::CommonGet('User',$modelData);
        };
        $userInfo = $this->getUserInfo();
		$data['headImgUrl'] = $userInfo['headimgurl'];
		$data['nickname'] = $userInfo['nickname'];

        if(count($user['data'])>0){
            
            $uid = $user['data'][0]['id'];
            $modelData = [];
            $modelData['data']['nickname'] = isset($data['nickname'])?$data['nickname']:'';
            $modelData['data']['headImgUrl'] = isset($data['headImgUrl'])?$data['headImgUrl']:'';
            $modelData['searchItem'] = ['id'=>$uid];
            $modelData['FuncName'] = 'update';
            $res = BeforeModel::CommonSave('User',$modelData);

        }else{

            $modelData = [];
            if(isset($data['data'])){
                $modelData['data'] = $data['data'];
            };
            $modelData['data']['nickname'] = isset($data['nickname'])?$data['nickname']:'';
            $modelData['data']['headImgUrl'] = isset($data['headImgUrl'])?$data['headImgUrl']:'';
            $modelData['data']['openid'] = $openid;
            $modelData['data']['type'] = 0;
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
        $user=BeforeModel::CommonGet('user',$modelData);
        $modelData = [];
        $modelData['searchItem']['id'] = $user['data'][0]['thirdapp_id'];
        $thirdApp=BeforeModel::CommonGet('ThirdApp',$modelData);
        
        $user['data'][0]['thirdApp'] = $thirdApp['data'][0];

        $userNo=$user['data'][0]['user_no'];

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


    public function getUserInfo()  
    {   
        $userinfo_url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$this->access_token['access_token'] ."&openid=" . $this->access_token['openid']."&lang=zh_CN";  
        $userinfo_json = $this->https_request($userinfo_url);  
        $userinfo_array = json_decode($userinfo_json, TRUE);  
        return $userinfo_array;  
    }


    public function getThirdConfig()
    {

    	$modelData = [];
        $modelData['searchItem']['id'] = $this->thirdapp_id;
        $thirdRes=BeforeModel::CommonGet('ThirdApp',$modelData);
		$thirdRes = $thirdRes['data'];
		if(count($thirdRes)>0){
            $thirdRes = $thirdRes[0];
            return $thirdRes;
		}else{
			return false;
		}
	}


	public function saveUser($userInfo)
	{
		//return $userInfo;
		$headimgurl['name'] = 'headimg';
		$headimgurl['url'] = $userInfo['headimgurl'];
		$headimgurl = json_encode($headimgurl);
		$adduserRes = Db::execute('insert into user (wx_openid,headimgurl,nickname ) values (?, ?, ?)',[$userInfo['openid'],$headimgurl,$userInfo['nickname']]);
		if($adduserRes){
			return $adduserRes;
		}else{
			return false;
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