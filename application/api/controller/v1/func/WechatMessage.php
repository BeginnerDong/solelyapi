<?php

namespace app\api\controller\v1\func;



use think\Request as Request; 

use think\Controller;

use think\Cache;

use think\Loader;

use app\api\controller\v1\weFunc\Project as WxProject;

use app\api\model\Common as CommonModel;

use app\api\controller\BaseController;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


//模板相关

class WechatMessage extends BaseController{


    //发送模板消息

    public static function sendMessage()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        $res = WxProject::sendMessage($data);

    }

}