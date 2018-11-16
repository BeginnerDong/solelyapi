<?php

namespace app\api\service\project;

use app\api\model\Common as CommonModel;

use think\Exception;

use think\Model;

use think\Cache; 


use app\api\model\User;

use app\api\model\UserInfo;

use app\api\model\Label;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


class Solely{


    function __construct($data){


    }

    //绑定情侣

    public static function binding($data){

        if(!Cache::get($data['token'])){
            throw new ErrorMessage([
                'msg'=>'token已失效',
                'solelyCode' => 200000
            ]);
        }

        if (!isset($data['user_no'])) {
            throw new ErrorMessage([
                'msg'=>'缺少关键信息user_no',
            ]);
        }

        $userinfo = Cache::get($data['token']);

        //获取绑定情侣的信息
        $modelData = [];

        $modelData['searchItem'] = [
            'user_no'=>$orderInfo['user_no']
        ];

        $binginfo =  CommonModel::CommonGet('User',$modelData);

        if(count($binginfo['data'])==0){
            throw new ErrorMessage([
                'msg' => 'userInfo未创建',
            ]);
        };

        $binginfo = $binginfo['data'][0];

        if(isset($binginfo['passage1'])||isset($userinfo['passage1'])){
            throw new ErrorMessage([
                'msg' => '用户已绑定情侣',
            ]);
        }

        $bindnum ='B'.strtoupper(dechex(date('m'))).date('d').substr(time(),-5).substr(microtime(),2,5).rand(0,99);

        $userone['FuncName'] = 'update';
        $userone['data']['passage1'] = $bindnum;
        $userone['searchItem']['user_no'] = $userinfo['user_no'];
        $saveone =  CommonModel::CommonSave("User",$userone);

        $usertwo['FuncName'] = 'update';
        $usertwo['data']['passage1'] = $bindnum;
        $usertwo['searchItem']['user_no'] = $binginfo['user_no'];
        $savetwo =  CommonModel::CommonSave("User",$usertwo);

        if ($saveone>0&&$savetwo>0) {
            throw new SuccessMessage([
                'msg' => '绑定成功',
            ]);
        }else{
            throw new ErrorMessage([
                'msg' => '绑定失败',
            ]);
        }

    }
}