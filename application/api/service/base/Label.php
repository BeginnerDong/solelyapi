<?php
namespace app\api\service\base;

use app\api\service\beforeModel\Common as BeforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\validate\CommonValidate;


use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Label{

    
    
    private static $filterArr = ['wx_mainImg'];

    function __construct($data){
        
        
    }

    //添加admin时判断name是否重复
    
    public static function add($data,$inner=false){

            (new CommonValidate())->goCheck('five',$data);
            checkTokenAndScope($data,20);

            

            $data = preAdd($data);
            $res = BeforeModel::CommonSave("Label",$data);
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


    public static function get($data,$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,20);
        
        $data = preGet($data);
        $res = BeforeModel::CommonGet("Label",$data);
        $res = resDeal($res,self::$filterArr);

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

        (new CommonValidate())->goCheck('two',$data);
        checkTokenAndScope($data,20);


        $modelData['map']['id'] = $data['id'];
        $revise =BeforeModel::CommonGet("Label",$modelData);
        
        if($revise){
            $revise = $revise[0];
            $data['thirdapp_id'] = $revise['thirdapp_id'];
            checkTokenAndScope($data,0);
        }else{
            throw new ErrorMessage([
                'msg'=>'您所'.$key.'的ID不存在'
            ]);
        };

        $search = ['id'=>$data['id']];
        unset($data['id']);
        $data = preUpdate($data);
        $res = BeforeModel::CommonSave("Label",$data,$search);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
           
    }

    public static function delete($data,$inner=false){

        (new CommonValidate())->goCheck('two',$data);
        checkTokenAndScope($data,20);

        
        $modelData = [];
        $modelData['token'] = $data['token'];
        $modelData['id'] = $data['id'];
        $modelData['status'] = -1;
        
        return self::update($modelData,"删除",$inner);

    }

    public static function realDelete($data,$key='真实删除',$inner=false){

        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,50);
        
        $data = preSearch($data);
        $res = BeforeModel::CommonDelete("Label",$data);
        if($inner){
            return $res;
        }else{
            dealUpdateRes($res,$key); 
        };
        
    }
}