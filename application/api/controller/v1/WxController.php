<?php
/**
 * Created by 董博明.
 * Author: 董博明
 * Date: 2018/4/9
 * Time: 19:23
 */

namespace app\api\controller\v1;
use think\Controller;
use think\Db;
use think\Request as Request;
use think\Loader;
use app\api\model\Common as CommonModel;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

Loader::import('WxDevelope.include', EXTEND_PATH,'.php');


class WxController extends Controller{

	protected $thirdapp_id;
	protected $openid;
	protected $parent_id;

	public function index(){

		//接受身份识别参数
		$data = Request::instance()->param();
 
    	
        if(isset($data["echostr"])){  
	        if($this->checkSignature($data)){  
	            echo $data["echostr"]; 
	            exit;   
	        }else{
	        	return false;
	        };
        };

		$this->thirdapp_id = $data['thirdapp_id'];
		$config = $this->getThirdConfig();
		//入口判定access_token有效期
		if($config){
			if($config['access_token']&&$config['access_token_expire']>time()){
			}else{
				$accessRes = $this->getAccessToken($config);
				//重新获取config信息
				$config = $this->getThirdConfig();
			}; 
		}else{
			return false;
		};
		$api = new \WeChat\Receive($config);
		$this->openid = $api->getReceive('FromUserName');
		$msgType = $api->getMsgType();
		if ($msgType=="event") {
			$event = $api->getReceive('Event');
			$dealevent = $this->dealEvent($event,$api);
		}else{
			$api->transferCustomerService()->reply();
		};
		
	}

	public function checkSignature($data)  
    {  
        $signature = $data["signature"];  
        $timestamp = $data["timestamp"];  
        $nonce = $data["nonce"];      
        $token = 'SOLELY';  
        $tmpArr = array($token, $timestamp, $nonce);  
        sort($tmpArr);  
        $tmpStr = implode( $tmpArr );  
        $tmpStr = sha1( $tmpStr );  
          
        if( $tmpStr == $signature ){  
            return true;  
        }else{  
            return false;  
        }  
    }  


	public function storeUnionid(){

		$userRes = Db::query('select * from user where wx_openid=?',[$this->openid]);

	    if($userRes){
	    	if($userRes[0]['unionid']){
	    	
	    	}else{

	    		$userInfoRes = $this->getUserInfo();
	    		$userinfo['unionid'] = $userInfoRes['unionid'];
	    		$search['wx_openid'] = $userInfoRes['openid'];
	    		$saveUserRes = $this->updateUser($userinfo,$search);
	    		return $saveUserRes;
	    	}

	    }else{

	    	$userInfoRes = $this->getUserInfo();

	    	$checkunionid = Db::query('select * from user where unionid=?',[$userInfoRes['unionid']]);
	    	if ($checkunionid) {
	    		$userinfo['wx_openid'] = $userInfoRes['openid'];
	    		$search['unionid'] = $userInfoRes['unionid'];
	    		$saveUserRes = $this->updateUser($userinfo,$search);
	    		return $saveUserRes;
	    	}else{
	    		$saveUserRes = $this->addUser($userInfoRes);
	    		return $saveUserRes;
	    	}
	    }
	}

	public function getUserInfo(){

		$config = $this->getThirdConfig();		

		if($config['access_token']){
			$str = "http://api.weixin.qq.com/cgi-bin/user/info?access_token=".$config['access_token']."&openid=".$this->openid;
			$userInfoRes = curl_get($str);
			if($userInfoRes){
				$userInfo = json_decode($userInfoRes,true);
				return $userInfo;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function getThirdConfig(){
		
		$thirdRes = Db::query('select * from third_app where id=?',[$this->thirdapp_id]);
		if($thirdRes){
			$config['token'] = $thirdRes[0]['wx_token'];
			$config['appid'] = $thirdRes[0]['wx_appid'];
			$config['appsecret'] = $thirdRes[0]['wx_appsecret'];
			$config['encodingaeskey'] = $thirdRes[0]['encodingaeskey'];
			$config['access_token'] = $thirdRes[0]['access_token'];
			$config['access_token_expire'] = $thirdRes[0]['access_token_expire'];
			return $config;
		}else{
			return false;
		}
	}

	public function getAccessToken($config){

		$accessRes = curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config["appid"]."&secret=".$config["appsecret"]);
		if($accessRes){
			$accessRes = json_decode($accessRes,true);
			$thirdInfo['access_token'] = $accessRes['access_token'];
			$thirdInfo['access_token_expire'] = time()+7000;
			$updateThirdAppRes = $this->updateThirdApp($thirdInfo);
			return $accessRes['access_token'];
		}else{
			return fasle;
		}
	} 

	public function addUser($userInfo){

		$headimgurl = json_encode(["0"=>['name'=>'headimg','url'=>$userInfo['headimgurl']]]);
		$adduserRes = Db::execute('insert into user (wx_openid, unionid ,thirdapp_id,member_id,nickname,headimgurl,create_time) values (?,?,?,?,?,?,?)',[$userInfo['openid'],$userInfo['unionid'],$this->thirdapp_id,$this->parent_id,$userInfo['nickname'],$headimgurl,time()]);
		if($adduserRes){
			return $adduserRes;
		}else{
			return false;
		}
	}

	public function updateUser($userInfo,$search)
	{
		$sqlString = 'update user set ';
		foreach ($userInfo as $key => $value) {
		    if($key!="id"){
		    	$sqlString .= $key." ='".$value."',";
		    };
		};
		$sqlString = rtrim($sqlString, ',');
		$map = ' where ';
		foreach ($search as $k => $v) {
		    $map .= $k." ='".$v."',";
		};
		$map = rtrim($map, ',');
		$sqlString .= $map;
		$updateUserRes = Db::execute($sqlString);
		if($updateUserRes){
			return $updateUserRes;
		}else{
			return false;
		}
	}

	public function updateThirdApp($thirdInfo)
	{
		
		$sqlString = 'update third_app set ';
		foreach ($thirdInfo as $key => $value) {
		    if($key!="id"){
		    	$sqlString .= $key." ='".$value."',";
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

	public function dealEvent($event,$api)
	{	
		switch ($event) {
			case 'subscribe':
				$addlog = Db::execute('insert into wxlog (title,content,create_time) values (?, ?,?)',[$event,$this->openid,time()]);
				$EventKey = $api->getReceive('EventKey');
				if (is_null($EventKey)||empty($EventKey)) {
					$this->parent_id = 0;
				}else{
					$addlog = Db::execute('insert into wxlog (title,content,create_time) values (?, ?,?)',[$event,$EventKey,time()]);
					$parent_id = substr($EventKey,8);
					//判定父级情况
					$memberinfo = Db::query('select * from member where id=?',[$parent_id]);
					if ($memberinfo) {
		                if ($memberinfo[0]['status']==1) {
		                    $this->parent_id = $parent_id;
		                }else{
		                    $this->parent_id = 0;
		                }
		            }else{
		                $this->parent_id = 0;
		            }
				}
				$storeUnionidRes = $this->storeUnionid();
				// return $storeUnionidRes;
				break;
			case 'CLICK':
				$EventKey = $api->getReceive('EventKey');
				if (is_null($EventKey)) {
					$addlog = Db::execute('insert into wxlog (title,content,create_time) values (?, ?,?)',[$event,$this->openid,time()]);
				}else{
					$addlog = Db::execute('insert into wxlog (title,content,create_time) values (?, ?,?)',[$event,$EventKey,time()]);
					//获取回复信息
					$message = Db::query('select * from wxmenu where id=?',[$EventKey]);
					$config = $this->getThirdConfig();
					$api = new \WeChat\Receive($config);
					$reply = $api->text($message[0]['description'])->reply();
				}
				break;
			default:
				$addlog = Db::execute('insert into wxlog (title,content,create_time) values (?, ?,?)',[$event,$this->openid,time()]);
				$this->parent_id = 0;
				break;
		}
	}

	public function pushMenu()
	{
		$data = $this->getMenudata();

		foreach ( $data as $k => $d ) {
			if ($d ['parentid'] != 0)
				continue;
			$tree  [$d ['id']] = $this->dealMenudata( $d );
			unset ( $data [$k] );
		}
		foreach ( $data as $k => $d ) {
			$tree  [$d ['parentid']] ['sub_button'] [] = $this->dealMenudata( $d );
			unset ( $data [$k] );
		}
		$menudata = array ();
		foreach ( $tree  as $k => $d ) {
			$menudata  [] = $d;
		}

		$data = Request::instance()->param();
		$this->thirdapp_id = $data['thirdapp_id'];
		$config = $this->getThirdConfig();
		if($config){
			if($config['access_token']&&$config['access_token_expire']>time()){
				$access_token = $config['access_token'];
			}else{
				$access_token = $this->getAccessToken($config);
			}; 
		}else{
			return false;
		};
		$delurl = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$access_token;
		$pushurl = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		$button = array('button' => $menudata);
		$delmenu = curl_get($delurl);
		if ($delmenu) {
			$delmenu = json_decode($delmenu,true);
			// return $delmenu['errmsg'];
		}else{
			return false;
		}
		$createmenu = $this->curl_wxpost($pushurl,$button);
		if ($createmenu) {
			$createmenu = json_decode($createmenu,true);
			// return $createmenu['errmsg'];
			echo json_encode(['msg'=>'更新菜单成功','solely_code'=>100000]);
		}else{
			return false;
		}
	}

	public function getMenudata()
	{
		$list = Db::query('select * from wxmenu where status=1 order by listorder desc');
		// 取一级菜单
		foreach ( $list as $k => $vo ) {
			if ($vo ['parentid'] != 0) 
				continue;
			$one_arr[$vo['id']] = $vo;
			unset ( $list [$k] );
		}
		foreach ( $one_arr as $p ) {
			$data [] = $p;
			$two_arr = array ();
			foreach ( $list as $key => $l ) {
				if ($l ['parentid'] != $p ['id'])
					continue;
				$two_arr [] = $l;
				unset ( $list [$key] );
			}
			$data = array_merge($data,$two_arr);
		}
		return $data;
	}

	public function sendMessagePrograme(){


		$data = Request::instance()->param();
		$this->thirdapp_id = $data['thirdapp_id'];
		$config = $this->getThirdConfig();
		if($config){
			if($config['access_token']&&$config['access_token_expire']>time()){
				$access_token = $config['access_token'];
			}else{
				$access_token = $this->getAccessToken($config);
			}; 
		}else{
			return false;
		};

		$modelData = [];
        $modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
        $modelData['searchItem']['status'] = 1;
        $modelData['getAfter'] =['formIdArray'=>[
        	'tableName'=>'Message',
        	'middleKey'=>'user_no',
        	'key'=>'key',
        	'condition'=>'=',
        	'info'=>['id','title']
        ]];
        $user=CommonModel::CommonGet('User',$modelData);

        if(count($user['data'])>0){
        	$userdId = [];
        	foreach ($user['data'] as $key => $value) {
        		if(isset($value['formIdArray']['title'])&&isset($value['formIdArray']['id'])){
        			array_push($userdId,$value['formIdArray']['id']);
        			$data_arr = array(
					  'keyword1' => array( "value" => 'test') 
					  'keyword2' => array( "value" => '即将开奖') 
					);
					$post_data = array (
					  // 用户的 openID，可用过 wx.getUserInfo 获取
					  "touser"           => $value['openid'],
					  // 小程序后台申请到的模板编号
					  "template_id"      => 'F2u-Q0XiDACB2WKiqd_ue8hEZMmPuZvc8KNhfnCNVBg',
					  // 点击模板消息后跳转到的页面，可以传递参数
					  "page"             => "/pages/index/index",
					  // 第一步里获取到的 formID
					  "form_id"          => $value['formIdArray']['title'],
					  // 数据
					  "data"             => $data_arr,
					  // 需要强调的关键字，会加大居中显示
					  //"emphasis_keyword" => "keyword2.DATA"
					);
					// 这里替换为你的 appID 和 appSecret
					$url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token;  
					// 将数组编码为 JSON
					$post_data = json_encode($post_data, true);   
					// 这里的返回值是一个 JSON，可通过 json_decode() 解码成数组
					$return = curl_wxpost($url, $post_data);
					var_dump($return);

        		};
        	};
        };

	}

	public function dealMenudata($d)
	{
		if ($d['parentid']==0&&empty($d['wmtype'])) {
			$res ['name'] =  $d ['name'] ;
		}else{
			switch ($d['wmtype']) {
				case 'view':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'view';
					$res ['url'] =  $d ['url'] ;
					break;
				case 'click':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'click';
					$res ['key'] =  $d ['key'] ;
					break;
				case 'miniprogram':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'miniprogram';
					$res ['url'] =  $d ['url'] ;
					$res ['appid'] =  $d ['appid'] ;
					$res ['pagepath'] =  $d ['pagepath'] ;
					break;
				default:
					break;
			}
		}
		return $res;
	}

	private function curl_wxpost($url, array $params = array())
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