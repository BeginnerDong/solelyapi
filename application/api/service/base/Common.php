<?php
namespace app\api\service\base;
use app\api\model\Common as CommonModel;
use think\Exception;
use think\Model;
use think\Cache;
use think\Loader;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;
use think\Request; 
//use Scope\Config as Config;


class Common{

    
    function __construct($data){
        
    }

    //添加admin时判断name是否重复
    
    public static function add($data,$inner=false){


        $scopeArr = [
            'Order'=>config('scope.one'),
            'Label'=>config('scope.one'),
            'Article'=>config('scope.one'),
            'Product'=>config('scope.one'),
            'Sku'=>config('scope.one'),
            'Message'=>config('scope.two'),
            'UserInfo'=>config('scope.two'),
            'UserAddress'=>config('scope.two'),
            'FlowLog'=>config('scope.two'),
            'Log'=>config('scope.two'),
            'Distribution'=>config('scope.six'),
            'WxFormId'=>config('scope.two'),
        ];

        if(isset($scopeArr[$data['modelName']])){
            $scope = $scopeArr[$data['modelName']];
            (new CommonValidate())->goCheck('one',$data);
            $data = checkTokenAndScope($data,$scope);
        }else{
            throw new ErrorMessage([
                'msg'=>'接口调用错误'
            ]);
        };
        
        $res =  CommonModel::CommonSave($data['modelName'],$data);
        
        if($inner){
            return $res;
        }else{
            if(is_array($res)){
                throw new SuccessMessage([
                    'msg'=>'添加成功',
                    'info'=>$res
                ]);
            }else if($res>0){
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

    

    public static function get($data,$inner=false){

        $scopeArr = [
            'Order'=>config('scope.two'),
            'OrderItem'=>config('scope.two'),
            'UserInfo'=>config('scope.two'),
            'Product'=>[],
            'Label'=>[],
            'Sku'=>[],
            'Article'=>[],
            'Message'=>config('scope.two'),
            'UserAddress'=>config('scope.two'),
            'FlowLog'=>config('scope.two'),
            'Log'=>config('scope.two'),
            'Distribution'=>config('scope.six'),
            'WxFormId'=>config('scope.two'),
        ];

        
        if(isset($scopeArr[$data['modelName']])){
            if(!empty($scopeArr[$data['modelName']])){
                $scope = $scopeArr[$data['modelName']];
                (new CommonValidate())->goCheck('one',$data);
                $data = checkTokenAndScope($data,$scope);
            }else if(isset($data['token'])){
                $data = checkTokenAndScope($data,config('scope.six'));
            };
        }else{
            throw new ErrorMessage([
                'msg'=>'接口调用错误'
            ]); 
        };

        $res =  CommonModel::CommonGet($data['modelName'],$data);
       
        if(isset($res['data'])&&count($res['data'])>0){
            $res['data'] = clist_to_tree($res['data']);
        }else if(!isset($res['data'])&&count($res)>0){
            $res = clist_to_tree($res);
        }else{
            throw new SuccessMessage([
                'msg'=>'查询结果为空',
                'info'=>$res
            ]);
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

        

        $scopeArr = [
            'Order'=>config('scope.two'),
            'Label'=>config('scope.one'),
            'Article'=>config('scope.one'),
            'Product'=>config('scope.one'),
            'Sku'=>config('scope.one'),
            'Message'=>config('scope.two'),
            'UserInfo'=>config('scope.two'),
            'UserAddress'=>config('scope.two'),
            'FlowLog'=>config('scope.two'),
            'Log'=>config('scope.two'),
            'Distribution'=>config('scope.six'),
            'WxFormId'=>config('scope.two'),
        ];
        if(isset($scopeArr[$data['modelName']])){
            $scope = $scopeArr[$data['modelName']];
            (new CommonValidate())->goCheck('one',$data);
            $data = checkTokenAndScope($data,$scope);
        }else{
            throw new ErrorMessage([
                'msg'=>'接口调用错误'
            ]); 
        };
        $res =  CommonModel::CommonSave($data['modelName'],$data);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
           
    }

    public static function delete($data,$inner=false){

        $scopeArr = [
            'Order'=>config('scope.two'),
            'Label'=>config('scope.one'),
            'Article'=>config('scope.one'),
            'Product'=>config('scope.one'),
            'Sku'=>config('scope.one'),
            'Message'=>config('scope.two'),
            'UserInfo'=>config('scope.two'),
            'UserAddress'=>config('scope.two'),
            'FlowLog'=>config('scope.two'),
            'Log'=>config('scope.two'),
            'WxFormId'=>config('scope.two'),
        ];
        if(isset($scopeArr[$data['modelName']])){
            $scope = $scopeArr[$data['modelName']];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,$scope);
        }else{
            throw new ErrorMessage([
                'msg'=>'接口调用错误'
            ]); 
        };
        
        $data['data'] = [];
        $data['data']['status'] = -1;
        $data['FuncName'] = 'update';
        return self::update($data,"删除",$inner);

    }

    public static function realDelete($data,$key='真实删除',$inner=false){
        $data = preSearch($data);

        $scopeArr = [
            'Order'=>['scope'=>[0,25],'behavior'=>['isMe']],
            'Label'=>['scope'=>[25,90],'behavior'=>['canChild']],
            'Article'=>['scope'=>[25,90],'behavior'=>['canChild']],
            'Product'=>['scope'=>[25,90],'behavior'=>['canChild']],
            'UserInfo'=>['scope'=>[0,20,90],'behavior'=>['isMe','canChild']],
            'UserAddress'=>['scope'=>[0,20],'behavior'=>['isMe']]
        ];
        if(isset($scope[$data['modelName']])){
            $scope = $scope[$data['modelName']];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,$scope);
        };
        
        
        $res =  CommonModel::CommonDelete($data['modelName'],$data);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
        
    }

    public static function compute($data,$inner=false){
       
        
        foreach ($data['data'] as $key => $value) {
            if(isset($value['searchItem'])){
                if(!isset($value['searchItem']['status'])){
                    $data['data'][$key]['searchItem']['status'] = 1;
                };
            }else{
                $value['searchItem'] = [
                    'status'=>1
                ];
            };
        };
        $res =  CommonModel::CommonCompute($data);
        if($inner){
            return $res;
        }else{
            throw new SuccessMessage([
                'msg'=>'计算成功',
                'info'=>$res
            ]);
        };
        
    }
}