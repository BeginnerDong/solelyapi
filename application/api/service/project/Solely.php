<?php

namespace app\api\service\project;

use app\api\model\Common as CommonModel;

use think\Exception;

use think\Model;

use think\Cache; 


use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


class Solely{


    function __construct($data){


    }

    /*
     *留言接口
     *不校验token
     */

    public static function addMessage($data){

        //获取绑定情侣的信息
        if (!isset($data['data']['user_type'])) {
            $data['data']['user_type'] = 0;
        }
        $modelData = [];
        $modelData['FuncName'] = 'add';
        $modelData['data'] = $data['data'];
        $addMsg =  CommonModel::CommonSave("Message",$modelData);

        if ($addMsg>0) {
            throw new SuccessMessage([
                'msg' => '留言成功',
            ]);
        }else{
            throw new ErrorMessage([
                'msg' => '留言失败',
            ]);
        }
    }
}