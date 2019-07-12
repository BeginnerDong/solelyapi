<?php

/**

 * Created by 董博明.

 * User: 董博明

 * Date: 2019/3/25

 * Time: 20:20

 */

namespace app\api\service\base;

use think\Cache;

use think\Loader; 

use think\Request as Request; 

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


/**

 * 微信公众号相关

 */

class Wx{


    private static function getThirdConfig($thirdapp_id)
    {

        $modelData = [];
        $modelData['searchItem']['id'] = $thirdapp_id;
        $thirdInfo = BeforeModel::CommonGet('ThirdApp',$modelData);

        if (count($thirdInfo['data'])>0) {
            $config['token'] = $thirdInfo['data'][0]['wx_token'];
            $config['appid'] = $thirdInfo['data'][0]['wx_appid'];
            $config['appsecret'] = $thirdInfo['data'][0]['wx_appsecret'];
            $config['encodingaeskey'] = $thirdInfo['data'][0]['encodingaeskey'];
            $config['access_token'] = $thirdInfo['data'][0]['access_token'];
            $config['access_token_expire'] = $thirdInfo['data'][0]['access_token_expire'];
            return $config;
        }else{
            throw new ErrorMessage([
                'msg'=>'项目信息错误',
            ]);            
        }

    }


    private static function getAccessToken($config)
    {

        $accessRes = curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config["appid"]."&secret=".$config["appsecret"]);
        if($accessRes){
            $accessRes = json_decode($accessRes,true);
            $modelData = [];
            $modelData['FuncName'] = 'update';
            $modelData['searchItem']['appid'] = $config['appid'];
            $modelData['searchItem']['appsecret'] = $config['appsecret'];
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



    /**
     * [sendMessage description]
     * @param  [type] $data [模板消息内容与模板id]
     * @param  [type] $User [用户信息，主要使用openid]
     * @return [type]       [description]
     */
    public static function sendMessage($data, $User)
    {
        $params = [];
        $params['touser'] = $User['openid'];
        $params['template_id'] = $data['template_id'];
        $params['data'] = $data['data'];

        $config = self::getThirdConfig($User['thirdapp_id']);

        if($config['access_token']&&$config['access_token_expire']>time()){
            $ACCESS_TOKEN = $config['access_token'];
        }else{
            $ACCESS_TOKEN = self::getAccessToken($config);
        }; 


        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$ACCESS_TOKEN;

        $send = self::curl_wxpost($url,$params);

        $send = json_decode($send,true);

		/*模板消息错误不抛出*/
		// if ($send['errcode']!=0) {
		// 	throw new ErrorMessage([
		// 		'msg'=>$send['errmsg'],
		// 	]);
		// }

    }


    private static function curl_wxpost($url, array $params = array())
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