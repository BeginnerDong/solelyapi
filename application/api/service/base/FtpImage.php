<?php

namespace app\api\service\base;



use think\Exception;

use think\Model;

use think\Cache;

use think\Request as Request; 





use app\api\model\Common as CommonModel;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;









class FtpImage{







    function __construct(){



         

    }

    

    public static function upload($data,$inner=false)

    {       

       



        (new CommonValidate())->goCheck('one',$data);

        checkTokenAndScope($data,config('scope.two'));

        

        $userinfo = Cache::get($data['token']);



        $file = request()->file();

        $file = $file['file'];

        if($file){

            // 移动到框架应用根目录/public/uploads/ 目录下

            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/'.$userinfo['thirdapp_id']);


            if($info){

                $saveName = $info->getSaveName();

                $pos = strrpos($saveName, '.');
                $ext = substr($saveName, $pos);

                $saveName = str_replace('\\','/',$saveName);

                $url = config('secure.base_url').'/public/uploads/'.$userinfo['thirdapp_id'].'/'.$saveName;

                $modelData = [];

                $modelData['data'] = array(

                    "title" => $saveName,

                    "thirdapp_id" => $userinfo['thirdapp_id'],

                    "user_no" => $userinfo['user_no'],

                    "path" => $url,

                    "prefix"  => 'uploads/'.$userinfo['thirdapp_id'],

                    "size" => $info->getSize(),

                    "type" => $ext,

                    "origin" => 2,

                    "behavior" => isset($data['behavior'])?$data['behavior']:1,

                    "param" => isset($data['param'])?$data['param']:1,

                    "create_time" => time(),

                );

                $modelData['FuncName'] = 'add';



                $res = CommonModel::CommonSave('File',$modelData);

                if ($res>0) {



                    if(!$inner){

                        throw new SuccessMessage([

                            'msg'=>'图片上传成功',

                            'info'=>['url'=>$url]

                        ]);

                    }else{

                        return $url;

                    };



                }else{

                    throw new ErrorMessage([

                        'msg' => '图片信息写入数据库失败',

                    ]);

                }



            }else{

                // 上传失败获取错误信息

                echo $file->getError();

            }    

        }



        

    }



    public static function uploadStream($data,$inner=false)

    {       

       

        if(!$inner){

            (new CommonValidate())->goCheck('one',$data);

            checkTokenAndScope($data,config('scope.two'));

            $userinfo = Cache::get($data['token']);

            $thirdapp_id = $userinfo['thirdapp_id'];

            $user_no = $userinfo['user_no'];

        }else{

            $thirdapp_id = $data['thirdapp_id'];

            $user_no = $data['user_no'];

        };

        $ext = $data['ext'];

        

        $saveName = substr(md5('streamSolely') , 0, 5). date('YmdH') . rand(0, 100) . '.' . $ext;

        $dir = ROOT_PATH . 'public' . DS . 'uploads/'.$thirdapp_id.'/'.date('Ymd');

        $path = $dir.'/'.$saveName;

        is_dir($dir) OR mkdir($dir, 0777, true); 



        if($data['stream']){

            // 移动到框架应用根目录/public/uploads/ 目录下



            $res = file_put_contents($path,$data['stream']);

            

            if($res){



                $url = config('secure.base_url').'/public/uploads/'.$thirdapp_id.'/'.date('Ymd').'/'.$saveName;

                $modelData = [];

                $modelData['data'] = array(

                    "thirdapp_id" => $thirdapp_id,

                    "title" => $saveName,

                    "user_no" => $user_no,

                    "path"        => $url,

                    "prefix"   => 'uploads/'.$thirdapp_id,

                    "size"        => $res,

                    "origin" => 2,

                    "behavior" => isset($data['behavior'])?$data['behavior']:1,

                    "type" => isset($data['type'])?$data['type']:1,

                    "param" => isset($data['param'])?$data['param']:'',

                    "create_time" => time(),

                );

                $modelData['FuncName'] = 'add';



                $res = CommonModel::CommonSave('File',$modelData);

                if ($res>0) {

                    if(!$inner){

                        throw new SuccessMessage([

                            'msg'=>'图片上传成功',

                            'info'=>['url'=>$url]

                        ]);

                    }else{

                        return $url;

                    };



                }else{

                    throw new ErrorMessage([

                        'msg' => '图片信息写入数据库失败',

                    ]);

                };



            }else{

                throw new ErrorMessage([

                    'msg' => '二进制流为空',

                ]);

            }    

        }



        

    }





  









}