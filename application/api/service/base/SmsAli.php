<?php
namespace app\api\service\base;
use think\Model;

//阿里短信
class SmsAli {




    public function sendMsg($data){

        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,config('scope.two'));



        if (!isset($data['params'])) {
            throw new TokenException([
                'msg' => '缺少短信模板信息',
                'solelyCode'=>225005
            ]);
        }
        $params = $data['params'];
        $accessKeyId = Cache::get($data['token'])['smsKey_ali'];
        $accessKeySecret = Cache::get($data['token'])['smsSecret_ali'];
        $code = createSMSCode(6);
        $codeinfo['code'] = $code;
        $codeinfo['phone'] = $params['PhoneNumbers'];
        Cache::set('code'.$data['token'],$codeinfo,600);
        if (!empty($params["TemplateParam"])&&is_array($params["TemplateParam"])) {
            $params['TemplateParam']['code'] = $code;
        }else{
            $params['TemplateParam'] = Array(
                "code"=>$code,
            );
        };
        if(!empty($params["TemplateParam"])&&is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        };

        $content = $this->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );
        if($content->Message=="OK"){
            throw new SuccessMessage([
                'msg'=>'发送成功',
            ]);
        }else{
            return $content;
        }


    }









    /**
     * 生成签名并发起请求
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    public function request($accessKeyId, $accessKeySecret, $domain, $params, $security=false) {
        $apiParams=array_merge(array (
            "SignatureMethod"=>"HMAC-SHA1",
            "SignatureNonce"=>uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion"=>"1.0",
            "AccessKeyId"=>$accessKeyId,
            "Timestamp"=>gmdate("Y-m-d\TH:i:s\Z"),
            "Format"=>"JSON",
        ),$params);
        ksort($apiParams);
        $sortedQueryStringTmp="";
        foreach($apiParams as $key=>$value) {
            $sortedQueryStringTmp.="&".$this->encode($key)."=".$this->encode($value);
        }
        $stringToSign="GET&%2F&".$this->encode(substr($sortedQueryStringTmp,1));
        $sign=base64_encode(hash_hmac("sha1",$stringToSign, $accessKeySecret."&",true));
        $signature=$this->encode($sign);
        $url=($security?'https':'http')."://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";
        try{
            $content=$this->fetchContent($url);
            return json_decode($content);
        }catch(\Exception $e) {
            return false;
        }
    }

    private function encode($str){
        $res=urlencode($str);
        $res=preg_replace("/\+/","%20",$res);
        $res=preg_replace("/\*/","%2A",$res);
        $res=preg_replace("/%7E/","~",$res);
        return $res;
    }
    
    private function fetchContent($url){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_TIMEOUT,5);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            "x-sdk-client"=>"php/2.0.0"
        ));
        if(substr($url,0,5)=='https') {
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        }
        $rtn=curl_exec($ch);
        if($rtn===false) {
            trigger_error("[CURL_".curl_errno($ch)."]:".curl_error($ch),E_USER_ERROR);
        }
        curl_close($ch);
        return $rtn;
    }
}