<?php
namespace app\api\service\base;

use think\Model;
use think\Exception;
use think\Cache;

use app\api\model\Distribution;

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class User{
    
    private static $filterArr = ['wx_mainImg'];
        function __construct($data){   
    }


    //添加admin时判断name是否重复
    public static function add($data,$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        if(!isset($data['data']['parent_no'])){
            $data['data']['parent_no'] = Cache::get($data['token'])['user_no'];
        };
       
        //判断用户名是否重复
        $modelData = [];
        $modelData['searchItem']['login_name'] = $data['data']['login_name'];
        $res = BeforeModel::CommonGet("User",$modelData);
        if(!empty($res['data'])){
            throw new ErrorMessage([
                'msg' => '用户名重复',
            ]);
        };
        $data['data']['user_no'] = makeUserNo();

        $modelData = [];
        $modelData['parent_no'] = $data['data']['parent_no'];
        $modelData['child_no'] = $data['data']['user_no'];
        $modelData['level'] = 1;
        $modelData['thirdapp_id'] = $data['data']['thirdapp_id'];
        $modelData['status'] = 1;
        $modelData['create_time'] = time();

        $distriRes = (new Distribution())->allowField(true)->save($modelData);
        self::addDistribution($data['data']['parent_no'],$data['data']['user_no'],$data['data']['thirdapp_id']);
        if(!isset($data['data']['primary_scope'])){
            $data['data']['primary_scope'] = 30;
        };
        $data['data']['status'] = 1;
        $data['data']['create_time'] = time();
        $data['saveAfter'] = [
            [
                'tableName'=>'UserInfo',
                'FuncName'=>'add',
                'data'=>[
                    'user_no'=>$data['data']['user_no'],
                    'thirdapp_id'=>$data['data']['thirdapp_id']
                ]
            ]
        ];
        $res = BeforeModel::CommonSave("User",$data);
        if($inner){
            return $res;
        }else{
            if($res>0){
                throw new SuccessMessage([
                    'msg'=>'添加成功',
                    'info'=>['id'=>$res]
                ]);
            }else{
                throw new ErrorMessage([
                    'msg'=>'添加失败'
                ]);
            };
        };
            
    }

    public static function addDistribution($parent_no,$userNo,$thirdapp_id){
        $res = (new Distribution())->where('child_no','=',$parent_no)->find();
        if($res){
            $content = [];
            $content['parent_no'] = $res['parent_no'];
            $content['child_no'] = $userNo;
            $content['level'] = $res['level']+1;
            $content['thirdapp_id'] = $thirdapp_id;
            $content['status'] = 1;
            $content['create_time'] = time();
            $distriRes = (new Distribution())->allowField(true)->save($content);
            if($res['parent_no']){
                self::addDistribution($res['parent_no'],$userNo,$thirdapp_id);
            };
        };
    }


    public static function get($data,$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        //return $data;
        $res = BeforeModel::CommonGet("User",$data);
        if($inner){
            return $res;
        }else{
            throw new SuccessMessage([
                'msg'=>'查询成功',
                'info'=>$res
            ]);   
        };
           
    }

    public static function update($data,$key='更新',$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        unset($data['data']['thirdapp_id']);
        $res = BeforeModel::CommonSave("User",$data);

        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
           
    }

    public static function delete($data,$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        
        $data['FuncName'] = 'update';
        $data['data'] = [];
        $data['data']['status'] = -1;
        
        return self::update($data,"删除",$inner);

    }

    public static function realDelete($data,$key='真实删除',$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        $res = BeforeModel::CommonDelete("User",$data);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
        
    }

}