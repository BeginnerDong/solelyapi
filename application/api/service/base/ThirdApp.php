<?php
namespace app\api\service\base;
use app\api\model\Common as CommonModel;
use think\Exception;
use think\Model;
use think\Cache;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


use app\api\service\base\User as UserService;

class ThirdApp{

    

    function __construct($data){
        
    }

    
    
    public static function add($data,$inner=false){

            (new CommonValidate())->goCheck('one',$data);
            $data = checkTokenAndScope($data,config('scope.two'));
            unset($data['searchItem']['thirdapp_id']);
            unset($data['data']['thirdapp_id']);
            
            

            //判断用户名是否重复
            $modelData = [];
            $modelData['searchItem'] = [];
            $modelData['searchItem']['name'] = $data['data']['name'];
            $modelData['token'] = $data['token'];
            $res =  CommonModel::CommonGet("ThirdApp",$modelData);
    
            if(!empty($res['data'])){
                throw new ErrorMessage([
                    'msg' => '用户名重复',
                ]);
            };

            
            $data['data']['parentid'] = Cache::get($data['token'])['thirdApp']['id'];
            $MainRes =  CommonModel::CommonSave("ThirdApp",$data);
            
            $newThirdAppInfo  = Cache::get($data['token']);
            array_push($newThirdAppInfo['thirdApp']['child_array'],intval($MainRes));
            Cache::set($data['token'],$newThirdAppInfo,3600);

            if($MainRes>0){

                $modelData = [];
                $modelData['data'] = [];
                $modelData['data']['child_array'] = Cache::get($data['token'])['thirdApp']['child_array'];
                if(!in_array(intval($MainRes), $modelData['data']['child_array'])){
                  array_push($modelData['data']['child_array'],intval($MainRes));
                };
 
                
                $modelData['searchItem']['id'] = Cache::get($data['token'])['thirdApp']['id'];
                $modelData['FuncName'] = 'update';                
                
                $res =  CommonModel::CommonSave('ThirdApp',$modelData);               
                if($res>0){
                    $modelData = [];
                    $modelData['data']['login_name'] = $data['data']['name'];
                    $modelData['data']['password'] = '111111';
                    $modelData['data']['user_type'] = 2;
                    $modelData['data']['primary_scope'] = 60;
                    $modelData['data']['thirdapp_id'] = $MainRes;
                    $modelData['token'] = $data['token'];
                    $modelData['FuncName'] = 'add';
                    $res = UserService::add($modelData,true);
                    if($res>0){
                        throw new SuccessMessage([
                            'msg'=>'添加成功',
                            'info'=>['id'=>$MainRes]
                        ]);
                    }else{
                        throw new ErrorMessage([
                            'msg'=>'创建相关登录用户失败'
                        ]);
                    };
                }else{
                    throw new ErrorMessage([
                        'msg'=>'更新相关父级失败'
                    ]);
                };
            }else{
                throw new ErrorMessage([
                    'msg'=>'添加失败'
                ]);
            };
    }


    public static function get($data,$inner=false){
        
        
        if(isset($data['token'])){
            $data = checkTokenAndScope($data,config('scope.two'));
            
            $data['searchItem']['id'] = $data['searchItem']['thirdapp_id'];
            unset($data['searchItem']['user_no']);
            unset($data['searchItem']['thirdapp_id']);
        };

        try{
            $res =  CommonModel::CommonGet("ThirdApp",$data);
            
        }catch(Exception $e){
            throw new ErrorMessage([
                'msg'=>'查询失败',
                'info'=>$e->getMessage()
            ]); 
        };
        
        if(isset($res['isTree'])&&isset($res['data'])&&count($res['data'])>0){
            $res['data'] = clist_to_tree($res['data']);
        };

         
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
        unset($data['searchItem']['thirdapp_id']);
        unset($data['data']['thirdapp_id']);


        if($key=='更新'&&isset($data['name'])){

            $modelData = [];
            $modelData['searchItem']['name'] = $data['name'];
            $modelData['token'] = $data['token'];
            $revise = self::get($modelData,true);            
            if($revise){
                
                throw new ErrorMessage([
                    'msg'=>'name重复'
                ]);
            };
        };
        

        $res =  CommonModel::CommonSave('ThirdApp',$data);
        
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
    }

    public static function delete($data,$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        
        $modelData = [];
        $modelData['token'] = $data['token'];
        $modelData['searchItem']['id'] = $data['searchItem']['id'];
        $modelData['data']['status'] = -1;
        $modelData['FuncName'] = 'update';
        
        return self::update($modelData,"删除",$inner);

    }

    public static function realDelete($data,$key='真实删除',$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        $data = checkTokenAndScope($data,config('scope.two'));
        
        $data = preSearch($data);
        $res =  CommonModel::CommonDelete("ThirdApp",$data);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };

    }





















    
}