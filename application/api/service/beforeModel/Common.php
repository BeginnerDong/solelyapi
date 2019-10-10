<?php
namespace app\api\service\beforeModel;

use think\Exception;
use think\Model;
use think\Cache;

use app\api\model\Common as CommonModel;

use app\api\service\beforeModel\Func as FuncService;

use app\api\service\project\Solely as SolelyService;

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
		
		/*检测过期优惠券*/
		if($dbTable=="UserCoupon"){
			
			self::checkCoupon($dbTable,$data);
			
		};

    	$final = CommonModel::CommonGet($dbTable,$data);
		
		if(isset($data['saveFunction'])){
			
			SolelyService::saveFunction($data['saveFunction']);
			
		};

        $final = self::getLimit($data,$final);

        $final['data'] = self::CommonGetAfter($data,$final['data']);

    	$final['data'] = self::getLimit($data,$final['data']);


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

        $FuncName = $data['FuncName'];

        if ($FuncName=='update'){
            
            CommonModel::imgManage($dbTable,$data);

        }

        $finalRes = CommonModel::CommonSave($dbTable,$data);
		
		if(isset($data['saveFunction'])){
			
			SolelyService::saveFunction($data['saveFunction']);
			
		};

        if($FuncName=='update'){

            self::CommonSaveAfter($dbTable,$data);

        }else{
      
            $data['searchItem'] = [
                'id'=>$finalRes
            ];
            CommonModel::imgManage($dbTable,$data);
            self::CommonSaveAfter($dbTable,$data);
        };

        return $finalRes;

    }



    public static function CommonDelete($dbTable,$data)
    {

    	$del = CommonModel::CommonDelete($dbTable,$data);
        
    }



	/**
	 * 前置搜索
	 * 分三层可以设置交/并集选项
	 * getBefore多个条件之间，在每个getBefore中设置type==merge为并集，默认交集
	 * 每个getBefore内的多个searchItem之间，在getBefore中设置searchType==merge为并集，默认交集
	 * 每个searchItem条件之间，在searchItem中数组每项的第三个参数设置merge为并集，默认交集
	 */
    public static function CommonGetPro($data)
    {
        if(isset($data['getBefore'])){
			$getBeforeData = [];
            $newSearchItem = [];
            foreach ($data['getBefore'] as $key => $value) {
				
				/*初始化每个getBefore的结果*/
                $search = [];
				
                foreach ($value['searchItem'] as $c_key => $c_value) {
					
					/*初始化每个searchItem的结果*/
					$searchItem = [];
                    foreach ($c_value[1] as $c_current) {
						
						/*初始化每个选项的结果*/
                        $c_search = [];
                        $map = [];
						
						if(is_array($c_value['key'])){
							$finalItem = '';
							foreach ($c_value['key'] as $cc_key => $cc_value) {
							    if($cc_key==0){
							        $finalItem = $getBeforeData[$c_value['key'][0]];
							    }else{
							        if ($finalItem&&isset($finalItem[$c_value['key'][$cc_key]])) {
										$finalItem = $finalItem[$c_value['key'][$cc_key]];
							        }else{
							            $finalItem = '';
							        };
							    };
							};
							if($finalItem){
							    $map[$c_key] = [$c_value['condition'],$finalItem];
							};
						}else{
							$map[$c_key] = [$c_value[0],$c_current];
						};

                        $modelData = [];
                        $modelData['searchItem'] = $map;
                        $res = CommonModel::CommonGet($value['tableName'],$modelData);
						/*记录结果复用*/
						$getBeforeData[$key] = $res;
						
                        foreach ($res['data'] as $ckey => $cvalue) {
                            array_push($c_search,$cvalue[$value['key']]);
                        };

                        if(empty($searchItem)){
                            $searchItem = $c_search;
                        }else{
							/*条件内部求交/并集*/
							if(isset($c_value[2])&&$c_value[2]=="merge"){
								array_merge($searchItem,$c_search);
							}else{
								$new = [];
								foreach($searchItem as $s_key => $s_value){
									if(in_array($s_value,$c_search)){
										array_push($new,$s_value);
									};
								};
								$searchItem = $new;
							};
						};
                    };
					
					/*记录结果复用*/
					$getBeforeData[$value['middleKey']] = [$value['condition'],$searchItem];
					
					if(empty($search)){
						$search = $searchItem;
					}else{
						/*多个searchItem之间求交/并集*/
						if(isset($value['searchType'])&&$value['searchType']=="merge"){
							array_merge($search,$searchItem);
						}else{
							$new = [];
							foreach($searchItem as $i_key => $i_value){
								if(in_array($i_value,$search)){
									array_push($new,$i_value);
								};
							};
							$search = $new;
						};
					}
                };
                if(!empty($search)){
                    if(isset($newSearchItem[$value['middleKey']])){
						if(isset($value['type'])&&$value['type']=="merge"){
							$newSearchItem[$value['middleKey']] = [$value['condition'],array_merge($search,$newSearchItem[$value['middleKey']][1])];
						}else{
							$new = [];
							foreach($search as $s_key => $s_value){
								if(in_array($s_value,$newSearchItem[$value['middleKey']][1])){
									array_push($new,$s_value);
								};
							};
							$newSearchItem[$value['middleKey']] = [$value['condition'],$new];
						};
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


    public static function getLimit($limit,$data)
    {

        if (isset($limit['getLimit'])&&is_array($limit['getLimit'])) {

            $limits = $limit['getLimit'];

            foreach ($data['data'] as $key => $value) {

                foreach ($limits as $c_key => $c_value) {
                    
                    if ($value['c_value']) {
                        
                        unset($value['c_value']);

                    }

                }

            }
            
        }

        return $data;

    }
	
	
	public static function checkCoupon($dbTable,$data){
	
		$coupons = CommonModel::CommonGet($dbTable,$data);
		
		foreach($coupons['data'] as $key => $value){
			
			if(isset($value['invalid_time'])&&($value['invalid_time']/1000<time())&&$value['use_step']==1){
				
				$modelData = [];
				$modelData['FuncName'] = 'update';
				$modelData['searchItem']['id'] = $value['id'];
				$modelData['data']['use_step'] = -1;
				$upCoupon = CommonModel::CommonSave('UserCoupon',$modelData);
				
			}
			
		}
		
	}
}