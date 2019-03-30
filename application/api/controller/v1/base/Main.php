<?php

namespace app\api\controller\v1\base;


use think\Request as Request; 
use think\Controller;
use think\Cache;
use think\Loader;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

use app\api\controller\BaseController;

use app\api\controller\v1\weFunc\Base as weFuncBase;
use app\api\controller\v1\weFunc\Menu as weFuncMenu;
use app\api\controller\v1\weFunc\Message as weFuncMessage;
use app\api\controller\v1\weFunc\Project as weFuncProject;
use app\api\controller\v1\weFunc\Source as weFuncSource;



use app\api\service\base\Common;
use app\api\service\base\CouponPay;
use app\api\service\base\FtpFile;
use app\api\service\base\Label;
use app\api\service\base\Pay;
use app\api\service\base\ProgramToken;
use app\api\service\base\QiniuFile;
use app\api\service\base\Qr;
use app\api\service\base\SaeStorage;
use app\api\service\base\SmsAli;
use app\api\service\base\SmsTencent;
use app\api\service\base\ThirdApp;
use app\api\service\base\User;
use app\api\service\base\WxPay;

use app\api\service\func\Common as CommonService;
use app\api\service\func\Coupon;
use app\api\service\func\Order;
use app\api\service\func\Token;

use app\api\service\project\Solely;




//模板相关

class Main extends BaseController{



    public static function Base()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        $service = self::loaderService($data['serviceName'],$data);

        $FuncName = $data['FuncName'];
        
        return $service::$FuncName($data);

    }



    public static function Func()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        if ($data['serviceName']=="Common") {

            $service = new CommonService($data);
            
        }else{
            
            $service = self::loaderService($data['serviceName'],$data);

        }

        $FuncName = $data['FuncName'];
        
        return $service::$FuncName($data);

    }



    public static function WeFunc()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        switch ($data['WeFuncName']) {
            case 'Base':
                $WeFunc = new weFuncBase($data);
                break;
            case 'Menu':
                $WeFunc = new weFuncMenu($data);
                break;
            case 'Message':
                $WeFunc = new weFuncMessage($data);
                break;
            case 'Project':
                $WeFunc = new weFuncProject($data);
                break;
            case 'Source':
                $WeFunc = new weFuncSource($data);
                break;
            default:
                break;
        }

        $FuncName = $data['FuncName'];

        return $WeFunc::$FuncName($data);

    }

    

    public static function Common()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        $service = self::loaderService('Common',$data);

        $FuncName = $data['FuncName'];
        
        return $service::$FuncName($data);

    }



    public static function Project()
    {

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        $service = self::loaderService($data['serviceName'],$data);

        $FuncName = $data['FuncName'];
        
        return $service::$FuncName($data);

    }



    public static function loaderService($serviceName,$data)
    {

        if($serviceName=='Common'){
            return new Common($data);
        }else if($serviceName=='CouponPay'){
            return new CouponPay($data);
        }else if($serviceName=='FtpFile'){
            return new FtpFile($data);
        }else if($serviceName=='Label'){
            return new Label($data);
        }else if($serviceName=='Pay'){
            return new Pay($data);
        }else if($serviceName=='ProgramToken'){
            return new ProgramToken($data);
        }else if($serviceName=='QinFile'){
            return new QinFile($data);
        }else if($serviceName=='Qr'){
            return new Qr($data);
        }else if($serviceName=='SaeStorage'){
            return new SaeStorage($data);
        }else if($serviceName=='SmsAli'){
            return new SmsAli($data);
        }else if($serviceName=='SmsTencent'){
            return new SmsTencent($data);
        }else if($serviceName=='ThirdApp'){
            return new ThirdApp($data);
        }else if($serviceName=='User'){
            return new User($data);
        }else if($serviceName=='WxPay'){
            return new WxPay($data);
        }else if($serviceName=='Coupon'){
            return new Coupon($data);
        }else if($serviceName=='Order'){
            return new Order($data);
        }else if($serviceName=='Token'){
            return new Token($data);
        }else if($serviceName=='Solely'){
            return new Solely($data);
        }else{
            throw new ErrorMessage([
                'msg' => 'serviceName有误',
            ]);
        };
    }
}