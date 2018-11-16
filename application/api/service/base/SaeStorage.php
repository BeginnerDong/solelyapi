<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2018/1/2
 * Time: 12:23
 */

namespace app\api\service\base;

use think\Exception;
use think\Model;
use think\Cache;
use think\Request as Request; 


use app\api\model\Common as CommonModel;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



// SAE
use think\sae\Storage as SAEStorage;

class SaeStorage{
	 

    

    /**
     * SAE storage图片上传
     */
    public function upload($data)
    {       
        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,config('scope.two'));


        $file = request()->file();
        $file = $file['file'];


        $filePath = $file['tmp_name'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);  //后缀
        $key =substr(md5($filePath) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        //实例化SAE类
        $s = new SAEStorage();
        $res = $s->putObjectFile($filePath, "public", $key);//视实际项目修改storage的名字，例子中的名字是"public"
        $userinfo = Cache::get($data['token']);



        if ($res) {
           
            $modelData = [];
            $modelData['data'] = array(
                "title" => $saveName,
                "thirdapp_id" => $userinfo['thirdapp_id'],
                "user_no" => $userinfo['user_no'],
                "path" => $url,
                "prefix" => 'uploads/'.$userinfo['thirdapp_id'],
                "size"  => $info->getSize(),
                "origin" => 1,
                "behavior" => isset($data['behavior'])?isset($data['behavior']):1,
                "type" => isset($data['type'])?isset($data['type']):1,
                "create_time" => time(),
            );
            $modelData['FuncName'] = 'add';

            $res = CommonModel::CommonSave('File',$modelData);
            if ($res>0) {

                throw new SuccessMessage([
                    'msg'=>'图片上传成功',
                    'info'=>['url'=>$url]
                ]);

            }else{
                throw new ErrorMessage([
                    'msg' => '图片信息写入数据库失败',
                ]);
            }
        }else{
            throw new ErrorMessage([
                'msg' => '图片上传失败',
            ]);
        }
    }
    
}