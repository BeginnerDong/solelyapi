<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\Func as FuncService;

use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Common {


    public static function CommonGet($dbTable,$data)
    {

    	$data = self::CommonGetPro($data);

        if(!$data){
            $final['data'] = [];
            return $final;
        };

    	$final = CommonModel::CommonGet($dbTable,$data);

    	$final['data'] = self::CommonGetAfter($data,$final['data']);

        if(isset($data['excelOutput'])){
            return exportExcel($data['excelOutput'],$final['data']);
        }else{
            return $final;
        };
        
    }



    public static function CommonSave($dbTable,$data)
    {
        
        $data = self::CommonGetPro($data);

        $data = FuncService::check($dbTable,$data);

        $data = self::CommonSavePro($data);

        $finalRes = CommonModel::CommonSave($dbTable,$data);
        
        $FuncName = $data['FuncName'];

        if($FuncName=='update'){

            self::CommonSaveAfter($dbTable,$data);

        }else{
      
            $data['searchItem'] = [
                'id'=>$finalRes
            ];
            self::CommonSaveAfter($dbTable,$data);
        };

        return $finalRes;

    }



    public static function CommonDelete($dbTable,$data)
    {

    	$del = CommonModel::CommonDelete($dbTable,$data);
        
    }



    public static function CommonGetPro($data)
    {
        if(isset($data['getBefore'])){
            $newSearchItem = [];
            foreach ($data['getBefore'] as $key => $value) {
                $search = [];
                foreach ($value['searchItem'] as $c_key => $c_value) {
                    foreach ($c_value[1] as $c_current) {
                        $c_search = [];
                        $map = [];
                        if(isset($value['fixSearchItem'])){
                            $map = $value['fixSearchItem'];
                        };
                        $map[$c_key] = [$c_value[0],$c_current];
                        $modelData = [];
                        $modelData['searchItem'] = $map;
                        $res = CommonModel::CommonGet($value['tableName'],$modelData);

                        foreach ($res['data'] as $ckey => $cvalue) {
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

                        $modelData = [];
                        $modelData['searchItem'] = $c_value['searchItem'];
                        $nRes = CommonModel::CommonGet($c_value['tableName'],$modelData);

                        if(!empty($nRes['data'])){
                            // $nRes[0] = resDeal($nRes[0]->toArray());
                            $nRes['data'][0] = resDeal($nRes['data'][0]);
                            foreach ($c_value['info'] as $info_key => $info_value) {
                               $new[$info_value] = $nRes['data'][0][$info_value];
                            };
                        };
                    }else if($searchItem){
                        $c_value['searchItem'][$c_value['key']] = $searchItem;

                        $modelData = [];
                        $modelData['searchItem'] = $c_value['searchItem'];
                        $nRes = CommonModel::CommonGet($c_value['tableName'],$modelData);

                        if(!empty($nRes['data'])){
                            $new = resDeal($nRes['data']);
                        };
                    };
                    
                    if(isset($c_value['compute'])){
                        foreach ($c_value['compute'] as $compute_key => $compute_value) {
                            $compute_value[2][$c_value['key']] = $searchItem;

                            $new[$compute_key] = CommonModel::CommonCompute($c_value['tableName'],$compute_value[0],$compute_value[1],$compute_value[2]);
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

                $modelData = [];
                $modelData['searchItem'] = $value['searchItem'];
                $Res = CommonModel::CommonGet($value['tableName'],$modelData);

                if(!empty($nRes['data'])){
                    $nRes['data'][0] = $nRes['data'][0]->toArray();
                    foreach ($value['info'] as $info_key => $info_value) {
                       $data['data'][$info_key] = $nRes['data'][0][$info_value];
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

                $modelData = [];
                $modelData['searchItem'] = $data['searchItem'];
                $res = CommonModel::CommonGet($table,$modelData);

                if(!$res){
                    throw new ErrorMessage([
                        'msg' => '关联saveAfter失败',
                    ]);
                };
            };
            
            
            foreach ($data['saveAfter'] as $value) {

                $value = FuncService::check($value['tableName'],$value);

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

	            $res = CommonModel::CommonSave($value['tableName'],$value);
            };

        };

    }
}