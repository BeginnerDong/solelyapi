<?php
/**
 * Created by 董博明.
 * User: 董博明
 * Date: 2018/4/2
 * Time: 18:57
 */

namespace app\api\controller\v1;

use think\Controller;
use app\api\controller\CommonController;
use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\api\model\SMSTencet as SMSModel;
use app\api\model\User as UserModel;
use app\api\model\ThirdApp as ThirdAppModel;
use think\Request as Request;
use app\api\service\Token as TokenService;
use think\Cache;

/**
 * 前端用户腾讯短信模块
 */
class SMSTencet extends CommonController{

    protected $beforeActionList = [
        // 'checkToken' => ['only' => 'sendMsg,updateUserPhone']
    ];

    public function sendMsg(){
        $data = Request::instance()->param();
        if (!isset($data['params'])) {
            throw new TokenException([
                'msg' => '缺少短信模板信息',
                'solelyCode'=>225005
            ]);
        }
        $param = $data['params'];
        //获取项目参数
        $userinfo = TokenService::getUserinfo($data['token']);
        $thirdinfo = ThirdAppModel::getThirdUserInfo($userinfo['thirdapp_id']);
        if (!isset($thirdinfo['smsID_tencet'])||!isset($thirdinfo['smsKey_tencet'])) {
            throw new TokenException([
                'msg' => '缺少短信接口参数',
                'solelyCode'=>225004
            ]);
        }
        $accessId = $thirdinfo['smsID_tencet'];
        $accessKey = $thirdinfo['smsKey_tencet'];
        $code = createSMSCode(6);
        //利用正则替换验证码
        $param = str_ireplace("captcha",$code,$param);
        //验证码放入缓存，时限1小时
        $codeinfo['code'] = $code;
        $codeinfo['phone'] = $data['phone'];
        Cache::set('code'.$data['token'],$codeinfo,600);

        $helper = new SMSModel($accessId,$accessKey);
        $result = $helper->sendWithParam(
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
        }
        if (!isset($data['phone'])) {
            throw new TokenException([
                'msg' => '缺少验证码',
                'solelyCode'=>225001
            ]);
        }
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