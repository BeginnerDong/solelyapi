<?php

namespace app\api\service\base;

use think\Model;

use think\Cache; 

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;

//腾讯云短信

class SmsTencent {

    

    private $url;
    private $appid;
    private $appkey;



    /**

     * 构造函数

     *

     * @param string $appid  sdkappid

     * @param string $appkey sdkappid对应的appkey

     */

    public function __construct($appid, $appkey)
    {
		
		$this->appid = $appid;

		$this->appkey = $appkey;
		
        $this->url = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms";

    }


    public function sendMsg($data){

    	// (new CommonValidate())->goCheck('one',$data); 

        // checkTokenAndScope($data,config('scope.two'));

        if (!isset($data['params'])) {

            throw new TokenException([

                'msg' => '缺少短信模板信息',

            ]);

        }

        $param = $data['params'];

        // $this->appid = Cache::get($data['token'])['smsID_tencet'];
        $this->appid = $this->appid;

        // $this->appkey = Cache::get($data['token'])['smsKey_tencet'];
		
        $this->appkey = $this->appkey;

        $code = createSMSCode(6);

        //利用正则替换验证码

        $param = str_ireplace("captcha",$code,$param);

        //验证码放入缓存，时限10分钟

        $codeinfo['code'] = $code;

        $codeinfo['phone'] = $data['phone'];

        Cache::set('smsCode'.$data['phone'],$codeinfo,600);

        $result = $this->sendWithParam(

            86,

            $data['phone'],

            $data['templId'],

            $param

        );

        $result = json_decode($result,true);

        if (isset($result['result'])) {

            if($result['result']==0){

                throw new SuccessMessage([

                    'msg'=>'发送成功',

                ]);

            }else{

                return $result;

            }

        }else{

            return $result;

        }   

    }


    /**

     * 普通单发

     *

     * 普通单发需明确指定内容，如果有多个签名，请在内容中以【】的方式添加到信息内容中，否则系统将使用默认签名。

     *

     * @param int    $type        短信类型，0 为普通短信，1 营销短信

     * @param string $nationCode  国家码，如 86 为中国

     * @param string $phoneNumber 不带国家码的手机号

     * @param string $msg         信息内容，必须与申请的模板格式一致，否则将返回错误

     * @param string $extend      扩展码，可填空串

     * @param string $ext         服务端原样返回的参数，可填空串

     * @return string 应答json字符串，详细内容参见腾讯云协议文档

     */

    public function send($type, $nationCode, $phoneNumber, $msg,$extend = "", $ext = "")
    {

        $random = $this->getRandom();

        $curTime = time();

        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;



        // 按照协议组织 post 包体

        $data = new \stdClass();

        $tel = new \stdClass();

        $tel->nationcode = "".$nationCode;

        $tel->mobile = "".$phoneNumber;

        $data->tel = $tel;

        $data->type = (int)$type;

        $data->msg = $msg;

        $data->sig = hash("sha256",

            "appkey=".$this->appkey."&random=".$random."&time="

            .$curTime."&mobile=".$phoneNumber, FALSE);

        $data->time = $curTime;

        $data->extend = $extend;

        $data->ext = $ext;

        return $this->sendCurlPost($wholeUrl, $data);

    }



    /**

     * 指定模板单发

     *

     * @param string $nationCode  国家码，如 86 为中国

     * @param string $phoneNumber 不带国家码的手机号

     * @param int    $templId     模板 id

     * @param array  $params      模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数

     * @param string $sign        签名，如果填空串，系统会使用默认签名

     * @param string $extend      扩展码，可填空串

     * @param string $ext         服务端原样返回的参数，可填空串

     * @return string 应答json字符串，详细内容参见腾讯云协议文档

     */

    public function sendWithParam($nationCode, $phoneNumber, $templId = 0, $params,

        $sign = "", $extend = "", $ext = "")

    {

        $random = $this->getRandom();

        $curTime = time();

        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;



        // 按照协议组织 post 包体

        $data = new \stdClass();

        $tel = new \stdClass();

        $tel->nationcode = "".$nationCode;

        $tel->mobile = "".$phoneNumber;



        $data->tel = $tel;

        $data->sig = $this->calculateSigForTempl($this->appkey, $random,

            $curTime, $phoneNumber);

        $data->tpl_id = $templId;

        $data->params = $params;

        $data->sign = $sign;

        $data->time = $curTime;

        $data->extend = $extend;

        $data->ext = $ext;

        return $this->sendCurlPost($wholeUrl, $data);

    }



        /**

     * 发送请求

     *

     * @param string $url      请求地址

     * @param array  $dataObj  请求内容

     * @return string 应答json字符串

     */

    public function sendCurlPost($url, $dataObj)

    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HEADER, 0);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_POST, 1);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);



        $ret = curl_exec($curl);

        if (false == $ret) {

            // curl_exec failed

            $result = "{ \"result\":" . -2 . ",\"errmsg\":\"" . curl_error($curl) . "\"}";

        } else {

            $rsp = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if (200 != $rsp) {

                $result = "{ \"result\":" . -1 . ",\"errmsg\":\"". $rsp

                        . " " . curl_error($curl) ."\"}";

            } else {

                $result = $ret;

            }

        }



        curl_close($curl);



        return $result;

    }



    /**

     * 生成签名

     *

     * @param string $appid         sdkappid

     * @param string $appkey        sdkappid对应的appkey

     * @param string $curTime       当前时间

     * @param array  $phoneNumbers  手机号码

     * @return string  签名结果

     */

    public function calculateSigForTempl($appkey, $random, $curTime, $phoneNumber)

    {

        $phoneNumbers = array($phoneNumber);



        return $this->calculateSigForTemplAndPhoneNumbers($appkey, $random,

            $curTime, $phoneNumbers);

    }



    /**

     * 生成签名

     *

     * @param string $appid         sdkappid

     * @param string $appkey        sdkappid对应的appkey

     * @param string $curTime       当前时间

     * @param array  $phoneNumbers  手机号码

     * @return string  签名结果

     */

    public function calculateSigForTemplAndPhoneNumbers($appkey, $random,

        $curTime, $phoneNumbers)

    {

        $phoneNumbersString = $phoneNumbers[0];

        for ($i = 1; $i < count($phoneNumbers); $i++) {

            $phoneNumbersString .= ("," . $phoneNumbers[$i]);

        }



        return hash("sha256", "appkey=".$appkey."&random=".$random

            ."&time=".$curTime."&mobile=".$phoneNumbersString);

    }



    /**

     * 生成随机数

     *

     * @return int 随机数结果

     */

    public function getRandom()

    {

        return rand(100000, 999999);

    }

}