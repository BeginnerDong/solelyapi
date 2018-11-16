<?php
/**
 * Created by 董博明.
 * User: 董博明
 * Date: 2018/3/31
 * Time: 18:27
 */

namespace app\api\controller\v1;

use think\Controller;
use app\api\controller\CommonController;
use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\api\model\SMSAli as SMSModel;
use app\api\model\User as UserModel;
use app\api\model\ThirdApp as ThirdAppModel;
use think\Request as Request;
use app\api\service\Token as TokenService;
use think\Cache;

/**
 * 前端用户阿里短信模块
 */
class SMSAli extends CommonController{

    protected $beforeActionList = [
        // 'checkToken' => ['only' => 'sendMsg,updateUserPhone']
    ];

    public function sendMsg(){
        $data=Request::instance()->param();
        if (!isset($data['params'])) {
            throw new TokenException([
                'msg' => '缺少短信模板信息',
                'solelyCode'=>225005
            ]);
        }
        $params = $data['params'];
        //获取项目参数
        $userinfo = TokenService::getUserinfo($data['token']);
        $thirdinfo = ThirdAppModel::getThirdUserInfo($userinfo['thirdapp_id']);
        if (!isset($thirdinfo['smsKey_ali'])||!isset($thirdinfo['smsSecret_ali'])) {
            throw new TokenException([
                'msg' => '缺少短信接口参数',
                'solelyCode'=>225004
            ]);
        }
        $accessKeyId = $thirdinfo['smsKey_ali'];
        $accessKeySecret = $thirdinfo['smsSecret_ali'];
        // $params["PhoneNumbers"] = $data['phone'];
        // $params["SignName"] = $thirdinfo['smsSign_ali'];
        // $params["TemplateCode"] = $thirdinfo['smsCode_ali'];
        $code = createSMSCode(6);
        //验证码放入缓存，时限1小时
        $codeinfo['code'] = $code;
        $codeinfo['phone'] = $params['PhoneNumbers'];
        Cache::set('code'.$data['token'],$codeinfo,600);
        if (!empty($params["TemplateParam"])&&is_array($params["TemplateParam"])) {
            $params['TemplateParam']['code'] = $code;
        }else{
            $params['TemplateParam'] = Array(
                "code"=>$code,
            );
        }
        if(!empty($params["TemplateParam"])&&is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        $helper = new SMSModel();
        $content = $helper->request(
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
     * 验证码成功后修改用户电话信息
     * 接受参数
     * @param token phone code
     */
    public function updateUserPhone(){  
        $data = Request::instance()->param();
        if (!isset($data['phone'])) {
            throw new TokenException([
                'msg' => '缺少手机号',
                'solelyCode'=>225000
            ]);
        };
        if (!isset($data['code'])) {
            throw new TokenException([
                'msg' => '缺少验证码',
                'solelyCode'=>225001
            ]);
        };
        $userinfo = TokenService::getUserinfo($data['token']);
        //判断验证码是否正确
        $codeinfo = Cache::get('code'.$data['token']);
        $data['code'] = (int)$data['code'];
        if($data['code']==(int)$codeinfo['code']){
            $data['phone'] = $codeinfo['phone'];
            $user = UserModel::updateUserinfo($userinfo['id'],$data);
            if($user){
                throw new SuccessMessage([
                    'msg'=>'绑定手机成功',
                ]);
            }else{
                throw new TokenException([
                    'msg' => '绑定手机失败',
                    'solelyCode'=>225003
                ]);
            }
        }else{
            throw new TokenException([
                'msg' => '验证码不正确',
                'solelyCode'=>225002
            ]);
        }
    }
}