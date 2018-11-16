<?php

/**

 * Created by 董博明.

 * User: 董博明

 * Date: 2018/4/10

 * Time: 18:25

 */

namespace app\api\service\base;

use think\Cache;

use think\Request as Request; 

use app\api\model\Common as CommonModel;

use app\api\service\base\QiniuImage as QiniuImageService;

use app\api\service\base\FtpImage as FtpImageService;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;





/**

 * 用户获取场景二维码

 */

class Qr{



    

    public static function ProgramQrGet($data){



       //data需要appid,appsecret,qrInfo,output,thirdapp_id,user_no



        (new CommonValidate())->goCheck('one',$data);

        checkTokenAndScope($data,config('scope.two'));

        $thirdapp_id = Cache::get($data['token'])['thirdapp_id'];

        $user_no = Cache::get($data['token'])['user_no'];



        $modelData = [];

        $modelData['searchItem']['id'] = $thirdapp_id;

        $ThirdAppInfo=CommonModel::CommonGet('ThirdApp',$modelData);

        $ThirdAppInfo = $ThirdAppInfo['data'][0];

        $appid = $ThirdAppInfo['appid'];

        $appsecret = $ThirdAppInfo['appsecret'];

        $data = Request::instance()->param();





        $modelData = [];

        $modelData['searchItem'] = [

            'type'=>1,

            'user_no'=>$user_no,

            'param'=>json_encode($data['qrInfo']),

            'behavior'=>2

        ];



        $res = CommonModel::CommonGet('File',$modelData);

        

        if(count($res['data'])>0){

            throw new SuccessMessage([

                'msg'=>'获取二维码图片成功',

                'info'=>['url'=>$res['data'][0]['path']]

            ]);

        };





        $tokenurl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;

        $result = curl_get($tokenurl);



        if ($result) {

            $result = json_decode($result,true);

            $access_token = $result['access_token'];

            $codeurl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;

            $stream = curl_post($codeurl,$data['qrInfo']);



            if($data['output']=='url'){

                $modelData = [];

                $modelData['stream'] = $stream;

                $modelData['thirdapp_id'] = $thirdapp_id;

                $modelData['user_no'] = $user_no;

                $modelData['behavior'] = 2;

                $modelData['type'] = 1;

                $modelData['ext'] = $data['ext'];

                $modelData['param'] = json_encode($data['qrInfo']);

                FtpImageService::uploadStream($modelData,true);

                //QiniuImageService::upload($modelData,true);

            }else{

                $result=self::data_uri($stream,'image/png');

                return $result;

            };

            

        }

    }



    public static function CommonQrGet($data){



       //data需要appid,appsecret,qrInfo,output,thirdapp_id,user_no

        

        (new CommonValidate())->goCheck('one',$data);

        checkTokenAndScope($data,config('scope.two'));

        $thirdapp_id = Cache::get($data['token'])['thirdapp_id'];

        $user_no = Cache::get($data['token'])['user_no'];

        



        $modelData = [];

        $modelData['searchItem'] = [

            'type'=>1,

            'user_no'=>$user_no,

            'param'=>$data['param'],

            'behavior'=>2

        ];



        $res = CommonModel::CommonGet('File',$modelData);

        

        if(count($res['data'])>0){

            throw new SuccessMessage([

                'msg'=>'获取二维码图片成功',

                'info'=>['url'=>$res['data'][0]['path']]

            ]);

        };



        $url = 'http://qr.liantu.com/api.php?text=solelynet';

        $stream = file_get_contents('http://qr.liantu.com/api.php?text='.$data['param'], 'r');



        //var_dump($stream);

        if($data['output']=='url'){

            $modelData = [];

            $modelData['stream'] = $stream;

            $modelData['thirdapp_id'] = $thirdapp_id;

            $modelData['user_no'] = $user_no;

            $modelData['behavior'] = 2;

            $modelData['type'] = 1;

            $modelData['param'] = $data['param'];

            $modelData['ext'] = $data['ext'];



           

            $res = FtpImageService::uploadStream($modelData,true);

            //$res = QiniuImageService::upload($modelData,true);

            if($res){

                throw new SuccessMessage([

                    'msg'=>'获取二维码图片成功',

                    'info'=>['url'=>$res]

                ]); 

            };

        

        }else{

            $result=self::data_uri($stream,'image/png');

            return $result;

        };

        

    }





    public static function data_uri($contents, $mime)

    {

        $base64   = base64_encode($contents);

        return ('data:' . $mime . ';base64,' . $base64);

    }

}