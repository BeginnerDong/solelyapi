<?php
/**
 * Created by 七月.
 * Author: 七月
 * 微信公号：小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/3/19
 * Time: 3:00
 */

namespace app\api\behavior;


use think\Response;

class CORS
{
    public function appInit(&$params)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: *");
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Credentials:false');
        if(request()->isOptions()){
        	header('Access-Control-Allow-Origin: *');
	        header("Access-Control-Allow-Headers: Content-Type,Content-Length, Authorization, Accept,X-Requested-With");
	        header('Access-Control-Allow-Methods: *');
	        header('Access-Control-Allow-Credentials:false');
            exit();
        }
    }
}