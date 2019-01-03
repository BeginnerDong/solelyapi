<?php

namespace app\api\service\base;

use app\api\model\User;
use app\api\model\ThirdApp as ThirdAppModel;
use app\api\model\Distribution;

use app\api\service\DistributionMain as DistributionService;
use app\api\service\beforeModel\Common as BeforeModel;


use think\Model;
use think\Exception;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;
use think\Cache;



/**
 * 微信登录
 * 如果担心频繁被恶意调用，请限制ip
 * 以及访问频率
 */






class ProgrameToken {
    

    function __construct($data){
        //获取app_id，app_secret

    }

    /**
     * 登陆
     * 思路1：每次调用登录接口都去微信刷新一次session_key，生成新的Token，不删除久的Token
     * 思路2：检查Token有没有过期，没有过期则直接返回当前Token
     * 思路3：重新去微信刷新session_key并删除当前Token，返回新的Token
     */
    
    public static function get($data)
    {

        $modelData = [];
        $modelData['searchItem']['id'] = $data['thirdapp_id'];

        $ThirdAppInfo = BeforeModel::CommonGet('ThirdApp',$modelData);

        if(!count($ThirdAppInfo)>0){
            throw new ErrorMessage([
                'msg'=>'关联thirdappID错误'
            ]);
        };

        $ThirdAppInfo = $ThirdAppInfo['data'][0];
        $data['distribution_level'] = $ThirdAppInfo['distribution_level'];
        $wxLoginUrl = sprintf(
            config('wx.login_url'), $ThirdAppInfo['appid'], $ThirdAppInfo['appsecret'], $data['code']);
        $result = curl_get($wxLoginUrl);

        // 注意json_decode的第一个参数true
        // 这将使字符串被转化为数组而非对象

        $wxResult = json_decode($result, true);
        if (empty($wxResult)||array_key_exists('errcode', $wxResult)) {
            // 为什么以empty判断是否错误，这是根据微信返回
            // 规则摸索出来的
            // 这种情况通常是由于传入不合法的code
            // throw new Exception('获取session_key及openID时异常，微信内部错误');
            throw new ErrorMessage([
                'msg' => $wxResult['errmsg'],
                'errorCode' => $wxResult['errcode']
            ]);
        };
        return self::grantToken($wxResult,$data);



    }

    

        


    // 颁发令牌
    // 只要调用登陆就颁发新令牌
    // 但旧的令牌依然可以使用
    // 所以通常令牌的有效时间比较短
    // 目前微信的express_in时间是7200秒
    // 在不设置刷新令牌（refresh_token）的情况下
    // 只能延迟自有token的过期时间超过7200秒（目前还无法确定，在express_in时间到期后
    // 还能否进行微信支付
    // 没有刷新令牌会有一个问题，就是用户的操作有可能会被突然中断
    public static  function grantToken($wxResult,$data){
        // 此处生成令牌使用的是TP5自带的令牌
        // 如果想要更加安全可以考虑自己生成更复杂的令牌
        // 比如使用JWT并加入盐，如果不加入盐有一定的几率伪造令牌
        // $token = Request::instance()->token('token', 'md5');
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

            if(isset($data['saveAfter'])){
                $modelData['saveAfter'] = array_merge($data['saveAfter'],$modelData['saveAfter']);
            };

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
        $user=BeforeModel::CommonGet('User',$modelData);
        $modelData = [];
        $modelData['searchItem']['id'] = $user['data'][0]['thirdapp_id'];
        $thirdApp=BeforeModel::CommonGet('ThirdApp',$modelData);
        
        $user['data'][0]['thirdApp'] = $thirdApp['data'][0];

        $userNo=$user['data'][0]['user_no'];

        $token = generateToken();
        $user['data'][0]['session_key'] = $wxResult['session_key'];
        $cacheResult = Cache::set($token,$user['data'][0],7200);
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








}
