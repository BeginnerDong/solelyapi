<?php

namespace app\api\service\project;

use app\api\service\beforeModel\Common as BeforeModel;

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
    public static function addMessage($data)
    {
        //获取绑定情侣的信息
        if (!isset($data['data']['user_type'])) {
            $data['data']['user_type'] = 0;
        }
        $modelData = [];
        $modelData['FuncName'] = 'add';
        $modelData['data'] = $data['data'];
        $addMsg =  BeforeModel::CommonSave("Message",$modelData);

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


    /**
     * 修改账户权限
     */
    public static function setAuth($data)
    {

        $data = checkTokenAndScope($data,config('scope.two'));

        if (!isset($data['data']['auth'])) {
            throw new ErrorMessage([
                'msg' => '缺少权限信息',
            ]);
        }

        $modelData = [];
        $modelData['searchItem'] = $data['searchItem'];
        $o_authList = BeforeModel::CommonGet('Auth',$modelData);

        $o_auth = [];

        if (count($o_authList['data'])) {
            
            foreach ($o_authList['data'] as $l_key => $l_value) {
            
                array_push($o_auth,$l_value['path']);

            };

        }

        foreach ($data['data']['auth'] as $n_key => $n_value) {
            
            /*判断新增的权限*/
            if (!in_array($n_value,$o_auth)) {

                /*判断是否已删除*/
                $modelData = [];
                $modelData['searchItem']['user_no'] = $data['searchItem']['user_no'];
                $modelData['searchItem']['path'] = $n_value;
                $modelData['searchItem']['status'] = -1;
                $check = BeforeModel::CommonGet('Auth',$modelData);

                if (count($check['data'])>0) {

                    $modelData = [];
                    $modelData['FuncName'] = 'update';
                    $modelData['searchItem']['id'] = $check['data'][0]['id'];
                    $modelData['data']['status'] = 1;
                    $addAuth = BeforeModel::CommonSave('Auth',$modelData);
                   
                }else{

                    $modelData = [];
                    $modelData['FuncName'] = 'add';
                    $modelData['data']['user_no'] = $data['searchItem']['user_no'];
                    $modelData['data']['thirdapp_id'] = Cache::get($data['token'])['thirdapp_id'];
                    $modelData['data']['path'] = $n_value;
                    $addAuth = BeforeModel::CommonSave('Auth',$modelData);

                }

            }

        }

        foreach ($o_auth as $o_key => $o_value) {
            
            /*判断删除的权限*/
            if (!in_array($o_value,$data['data']['auth'])) {

                $modelData = [];
                $modelData['FuncName'] = 'update';
                $modelData['searchItem']['user_no'] = $data['searchItem']['user_no'];
                $modelData['searchItem']['path'] = $o_value;
                $modelData['data']['status'] = -1;
                $addAuth = BeforeModel::CommonSave('Auth',$modelData);

            }

        }

        throw new SuccessMessage([
            'msg' => '更新权限成功',
        ]);

    }


    /**
     * 物理删除图片
     */
    public static function realDelImg($data)
    {

        checkTokenAndScope($data,config('scope.two'));

        $img = BeforeModel::CommonGet('File',$data);

        if (count($img['data'])>0) {
            
           $img = $img['data'][0];
        
        }else{
            throw new ErrorMessage([
                'msg' => '图片不存在',
            ]);
        }

        $modelData = [];
        $modelData['searchItem']['id'] = $img['id'];
        $upImg = CommonModel::CommonDelete('File',$modelData);

        if ($img['origin']==2) {

            $path = ROOT_PATH.'/public/uploads/'.$img['thirdapp_id'].'/'.$img['title'];
            $realDel = unlink($path);

        }

        if (count($upImg)>0&&$realDel) {
            throw new SuccessMessage([
                'msg' => '删除成功',
            ]);
        }else{
            throw new ErrorMessage([
                'msg' => '删除失败',
            ]);
        }

    }


    /**
     * 模拟前端用户登录
     */
    public static function getToken($data)
    {
        $user = BeforeModel::CommonGet('User',$data);
        if (count($user['data'])==0) {
            throw new ErrorMessage([
                'msg' => '用户信息不存在'
            ]);
        }
        $user = $user['data'][0];
        $modelData = [];
        $modelData['searchItem']['id'] = $user['thirdapp_id'];
        $thirdApp = BeforeModel::CommonGet('ThirdApp',$modelData);
        $user['thirdApp'] = $thirdApp['data'][0];

        $token = generateToken();
        $cacheResult = Cache::set($token,$user,7200);
        if (!$cacheResult){
            throw new ErrorMessage([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        };
        throw new SuccessMessage([
            'msg'=>'登录成功',
            'token'=>$token,
            'info'=>$user
        ]);

    }
}