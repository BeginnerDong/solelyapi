<?php
namespace app\api\model;
use think\Model;
use think\Loader;
use think\Db;
use think\Cache;

use app\lib\exception\ErrorMessage;

use app\api\model\Article;
use app\api\model\Distribution;
use app\api\model\File;
use app\api\model\FlowLog;
use app\api\model\Label;
use app\api\model\Log;
use app\api\model\Message;
use app\api\model\Order;
use app\api\model\OrderItem;
use app\api\model\Product;
use app\api\model\Relation;
use app\api\model\Sku;
use app\api\model\ThirdApp;
use app\api\model\User;
use app\api\model\UserAddress;
use app\api\model\UserInfo;


class Common extends Model{

    


    public static function CommonGet($dbTable,$data)
    {

        $data = self::CommonGetPro($data);
        
        if(!$data){
            $final['data'] = [];
            return $final;
        };

        $model =Loader::model($dbTable);
        $sqlStr = preModelStr($data);
        if(!isset($data['searchItem']['status'])){
            $data['searchItem']['status'] = 1;
        };
        if(isset($data['paginate'])){  

            $pagesize = $data['paginate']['pagesize'];
            $paginate = $data['paginate'];
            $paginate['page'] = $data['paginate']['currentPage'];
            $sqlStr = $sqlStr."paginate(\$pagesize,false,\$paginate);";
            $res = eval($sqlStr);
            $res = $res->toArray();

            $final = [
                'total'=>$res['total'],
            ];
            $res = $model->dealGet(resDeal($res['data']));
            $res = self::CommonGetAfter($data,$res);
            
        }else{

            $sqlStr = $sqlStr."select();";
            $res = eval($sqlStr);
            $res = $model->dealGet(resDeal($res));
            $res = self::CommonGetAfter($data,$res);
            if($dbTable=='article'){
                $updateData = [];
                foreach ($res as $key => $value) {
                    array_push($updateData,['id'=>$value['id'],'view_count'=>$value['view_count']+1]);
                };
                $model->saveAll($updateData);
            };
            
        };

        if(isset($data['excelOutput'])){
            return exportExcel($data['excelOutput']['expTitle'] ,$data['excelOutput']['expCellName'],$res,$data['excelOutput']['fileName']);
        }else{
            $final['data'] = $res;
            return $final;
        };
        
        
    }



    public static function CommonSave($dbTable,$data)
    {
        
        
        $data = self::CommonGetPro($data);
        $model =self::loaderModel($dbTable);

        $sqlStr = preModelStr($data);
        $data['data'] = keepNum($data['data']);
        
        if(isset($data['data']['password'])){
            $data['data']['password'] = md5($data['data']['password']);
        };
        
        
        $FuncName = $data['FuncName'];
        Db::startTrans();
        try{
            if($FuncName=='update'){
                $data['data']['update_time'] = time();
                $model->dealUpdate($data);
                $data['data'] = jsonDeal($data['data']);
                $sqlStr = $sqlStr."update(\$data[\"data\"]);";
                $finalRes = eval($sqlStr);
                self::CommonSaveAfter($dbTable,$data);
            }else{
                if(isset($data['dataArray'])){
                    $finalRes = [];
                    foreach ($data['dataArray'] as $key => $value) {
                        $modelData = [];
                        $modelData['data'] = $value;
                        $modelData = $model->dealAdd($modelData);
                        $res = $model->isUpdate(false)->data($modelData['data'], true)->allowField(true)->save();
                        $finalRes['第'.($key+1).'条'] = $model->id;
                    };
                    
                }else{

                    $data = self::CommonSavePro($data);
                    $data = $model->dealAdd($data);
                    $data['data'] = jsonDeal($data['data']);
                    
                    $res = $model->allowField(true)->save($data['data']);
                    
                    $finalRes = $model->id;
                    //return $finalRes;
                    $data['searchItem'] = [
                        'id'=>$finalRes
                    ];
                    self::CommonSaveAfter($dbTable,$data);
                };
            };

            Db::commit(); 
            return $finalRes;

        } catch (\Exception $e) {
            // 回滚事务
            
            if(isset($e->msg)){
                throw new ErrorMessage([
                    'msg' => $e->msg,
                ]);
            }else{
                var_dump($e);
                //Handlerender($e);
                //throw new ExceptionHandler($e);
                //return json($e->getError(), 422);
            };
            
            Db::rollback();
        };
    }



    public static function CommonDelete($dbTable,$data)
    {
        $model =Loader::model($dbTable);
        $sqlStr = preModelStr($data);
        $sqlStr = $sqlStr."delete();";
        Db::startTrans();
        try{
            $res = eval($sqlStr);
            $model->realDeleteData($data);
            Db::commit(); 
            return $res;
        } catch (\Exception $e) {
            // 回滚事务
            if(isset($e->msg)){
                throw new ErrorMessage([
                    'msg' => $e->msg,
                ]);
            }else{
                var_dump($e);
            };
            Db::rollback();
        };
        
    }



    public static function CommonGetPro($data)
    {
        if(isset($data['getBefore'])){
            $newSearchItem = [];
            foreach ($data['getBefore'] as $key => $value) {
                $model =Loader::model($value['tableName']);
                $search = [];
                foreach ($value['searchItem'] as $c_key => $c_value) {
                    foreach ($c_value[1] as $c_current) {
                        $c_search = [];
                        $map = [];
                        if(isset($value['fixSearchItem'])){
                            $map = $value['fixSearchItem'];
                        };
                        $map[$c_key] = [$c_value[0],$c_current];
                        
                        $res = $model->where($map)->select();

                        foreach ($res as $ckey => $cvalue) {
                            array_push($c_search,$cvalue[$value['key']]);
                        };

                        if(empty($search)){
                            $search = $c_search;
                        }else{
                            $search = array_intersect($search,$c_search);
                        };

                    };
                };
                if(!empty($search)){
                    if(isset($newSearchItem[$value['middleKey']])){
                        $newSearchItem[$value['middleKey']] = [$value['condition'],array_intersect($search,$newSearchItem[$value['middleKey']][1])];
                    }else{
                        $newSearchItem[$value['middleKey']] = [$value['condition'],$search];
                    }; 
                };
            };
            if(!empty($newSearchItem)){
                $data['searchItem'] = array_merge($data['searchItem'],$newSearchItem);
            }else{
                $data = [];
            };
        };
        return $data;
    }

    public static function CommonGetAfter($data,$res)
    {
        
        if(isset($data['getAfter'])){

            foreach ($res as $key => $value) {

                $copyValue = $value;
                foreach ($data['getAfter'] as $c_key => $c_value) {
                    $new = [];

                    $model =Loader::model($c_value['tableName']);
                    if(is_array($c_value['middleKey'])){
                        $finalItem = '';
                        foreach ($c_value['middleKey'] as $cc_key => $cc_value) {
                            if($cc_key==0){
                                $finalItem = $copyValue[$c_value['middleKey'][0]];
                            }else{
                                if ($finalItem&&isset($finalItem[$c_value['middleKey'][$cc_key]])) {
                                    $finalItem = $finalItem[$c_value['middleKey'][$cc_key]];
                                }else{
                                    $finalItem = '';
                                };
                            };
                        };
                        if($finalItem){
                            $searchItem = [$c_value['condition'],$finalItem];
                        };
                    }else{
                        $searchItem = [$c_value['condition'],$copyValue[$c_value['middleKey']]];
                    };
                    
                    if(isset($c_value['info'])&&$searchItem){
                        $c_value['searchItem'][$c_value['key']] = $searchItem;
                        $nRes = $model->where($c_value['searchItem'])->select();
                        if(!empty($nRes)){
                            $nRes[0] = resDeal($nRes[0]->toArray());
                            foreach ($c_value['info'] as $info_key => $info_value) {
                               $new[$info_value] = $nRes[0][$info_value];
                            };
                        };
                    }else{
                        $c_value['searchItem'][$c_value['key']] = $searchItem;
                        $nRes = $model->where($c_value['searchItem'])->select();
                        if(!empty($nRes)){
                            $new = resDeal($nRes);
                        };
                    };
                    

                    if(isset($c_value['compute'])){
                        foreach ($c_value['compute'] as $compute_key => $compute_value) {
                            $compute_value[2][$c_value['key']] = $searchItem;
                            if($compute_value[0]!='count'){
                                $new[$compute_key] = $model->where($compute_value[2])->$compute_value[0]($compute_value[1]); 
                            }else{
                                $new['totalCount'] = $model->where($compute_value[2])->count(); 
                            };
                        };
                    };
                    $res[$key][$c_key] = [];
                    $res[$key][$c_key] = $new;
                    $copyValue[$c_key] = $new;
                   
                };
                
            };
            
        };

        return $res;
        
    }

    public static function CommonSavePro($data)
    {
        
        if(isset($data['saveBefore'])){
            $newSearchItem = [];
            foreach ($data['saveBefore'] as $value) {

                $CommonSavePro_model =Loader::model($value['tableName']);
                $Res = $CommonSavePro_model->where($value['searchItem'])->select();
                if(!empty($nRes)){
                    $nRes[0] = $nRes[0]->toArray();
                    foreach ($value['info'] as $info_key => $info_value) {
                       $data['data'][$info_key] = $nRes[0][$info_value];
                    };
                };
            };
        };
        return $data;

    }

    public static function CommonSaveAfter($table,$data)
    {
        
        if(isset($data['saveAfter'])){

            if(isset($value['data']['res'])||isset($value['searchItem']['res'])){
               $oldModel =self::loaderModel($table);
                $res = $oldModel->where($data['searchItem'])->find(); 
                if(!$res){
                    throw new ErrorMessage([
                        'msg' => '关联saveAfter失败',
                    ]);
                };
            };
            
            
            foreach ($data['saveAfter'] as $value) {
                $model =self::loaderModel($value['tableName']);
                if(isset($value['data']['res'])){
                    foreach ($value['data']['res'] as $data_key => $data_value) {
                        $value['data'][$data_key] = $res[$data_value];
                    };
                    unset($value['data']['res']);
                };
                
                if(isset($value['searchItem']['res'])){
                    foreach ($value['searchItem']['res'] as $searchItem_key => $searchItem_value) {
                        $value['searchItem'][$searchItem_key] = $res[$searchItem_value];
                    };
                    unset($value['searchItem']['res']); 
                };
                if($value['FuncName']=='add'){
                    $value = $model->dealAdd($value);
                    $model->allowField(true)->save($value['data']);
                }else{
                    $model->dealUpdate($value);
                    $model->where($value['searchItem'])->update($value['data']);
                };
            };

            

        };
        

    }



    public static function CommonCompute($data)
    {
        
        
            $res = [];
            $data = $data['data'];
            foreach ($data as $key => $value) {
                $new = [];

                $model =Loader::model($key);

                foreach ($value['compute'] as $compute_key => $compute_value) {
                    if($compute_value!='count'){
                       $new[$compute_key.$compute_value] = $model->where($value['searchItem'])->$compute_value($compute_key); 
                   }else{
                        $new['count'] = $model->where($value['searchItem'])->count(); 
                        
                   };
                };
                
                $res[$key] = $new;

            };
            
        return $res;
        
    }


    public static function loaderModel($dbTable){

        if($dbTable=='Article'){
            return new Article;
        }else if($dbTable=='Distribution'){
            return new Distribution;
        }else if($dbTable=='File'){
            return new File;
        }else if($dbTable=='FlowLog'){
            return new FlowLog;
        }else if($dbTable=='Label'){
            return new Label;
        }else if($dbTable=='Log'){
            return new Log;
        }else if($dbTable=='Message'){
            return new Message;
        }else if($dbTable=='Order'){
            return new Order;
        }else if($dbTable=='OrderItem'){
            return new OrderItem;
        }else if($dbTable=='Product'){
            return new Product;
        }else if($dbTable=='Relation'){
            return new Relation;
        }else if($dbTable=='Sku'){
            return new Sku;
        }else if($dbTable=='ThirdApp'){
            return new ThirdApp;
        }else if($dbTable=='User'){
            return new User;
        }else if($dbTable=='UserAddress'){
            return new UserAddress;
        }else if($dbTable=='UserInfo'){
            return new UserInfo;
        }else{
            throw new ErrorMessage([
                'msg' => 'tableName有误',
            ]);
        };

    }



    


}