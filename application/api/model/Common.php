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
use app\api\model\WxFormId;
use app\api\model\Coupon;
use app\api\model\UserCoupon;
use app\api\model\CouponRelation;
use app\api\model\Auth;
use app\api\model\PayLog;
use app\api\model\WxTemplate;


class Common extends Model{


    public static function CommonGet($dbTable,$data)
    {
        
        $model =self::loaderModel($dbTable);
        $sqlStr = preModelStr($data);
        if(!isset($data['searchItem']['status'])){
            $data['searchItem']['status'] = 1;
        };
		
		if (isset($data['compute'])) {
		    $new = [];
		    foreach ($data['compute'] as $compute_key => $compute_value) {
				$new[$compute_key] = self::CommonCompute($dbTable,$compute_value[0],$compute_value[1],$data['searchItem']);
		    };
		};	

        if ($dbTable=='Distribution'&&isset($data['searchItem'])&&isset($data['searchItem']['user_no'])) {
            unset($data['searchItem']['user_no']);
        };
        if ($dbTable=='Distribution'&&isset($data['searchItem'])&&isset($data['searchItem']['user_type'])) {
            unset($data['searchItem']['user_type']);
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
            
        }else if(isset($data['getOne'])){
			
			$sqlStr = $sqlStr."find();";
			$find = eval($sqlStr);
			if($find){
				$res[0] = $find;
				$res = $model->dealGet(resDeal($res));
			}else{
				$res = [];
			}
			
		}else{

            $sqlStr = $sqlStr."select();";
            $res = eval($sqlStr);
            $res = $model->dealGet(resDeal($res));
            if($dbTable=='Article'){
                $updateData = [];
                foreach ($res as $key => $value) {
                    array_push($updateData,['id'=>$value['id'],'view_count'=>$value['view_count']+1]);
                };
                $model->saveAll($updateData);
            };
            
        };
		
		if (isset($data['compute'])) {
		    $final['compute'] = $new;
		};
		
		/*过滤字段*/
		if(isset($data['info'])&&count($res)>0){
			$new = [];
			foreach($res as $res_key => $res_value){
				foreach ($data['info'] as $info_key => $info_value) {
				   $new[$res_key][$info_value] = $res_value[$info_value];
				};
			};
			$res = $new;
		};

        $final['data'] = $res;
        return $final;
        
    }



    public static function CommonSave($dbTable,$data)
    {
        
        $model =self::loaderModel($dbTable);

        $sqlStr = preModelStr($data);
        if (isset($data['data'])) {
            $data['data'] = keepNum($data['data']);
        };
        
        if(isset($data['data']['password'])){
            $data['data']['password'] = md5($data['data']['password']);
        };
        
        
        $FuncName = $data['FuncName'];
        Db::startTrans();
        try{

            if($FuncName=='update'){
				if(isset($data['data']['user_no'])){
					unset($data['data']['user_no']);
				};
                $data['data']['update_time'] = time();
                $model->dealUpdate($data);
                $data['data'] = jsonDeal($data['data']);
                $sqlStr = $sqlStr."update(\$data[\"data\"]);";
                $finalRes = eval($sqlStr);

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

                    $data = $model->dealAdd($data);
                    $data['data'] = jsonDeal($data['data']);
                    
                    $res = $model->allowField(true)->save($data['data']);
                    
                    $finalRes = $model->id;
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
        $model = self::loaderModel($dbTable);
        $sqlStr = preModelStr($data);
        $sqlStr = $sqlStr."delete();";
        Db::startTrans();
        try{
            $res = eval($sqlStr);
            // $model->realDeleteData($data);
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


    public static function CommonCompute($model,$method,$key,$map)
    {
        
        $model =self::loaderModel($model);

        if ($method!='count') {
            
            $num = $model->where($map)->$method($key); 

        }else{

            $num = $model->where($map)->count();

        }

        return $num;
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
        }else if($dbTable=='WxFormId'){
            return new WxFormId;
        }else if($dbTable=='Coupon'){
            return new Coupon;
        }else if($dbTable=='UserCoupon'){
            return new UserCoupon;
        }else if($dbTable=='CouponRelation'){
            return new CouponRelation;
        }else if($dbTable=='Auth'){
            return new Auth;
        }else if($dbTable=='PayLog'){
            return new PayLog;
        }else if($dbTable=='WxTemplate'){
            return new WxTemplate;
        }else{
            throw new ErrorMessage([
                'msg' => 'tableName有误',
            ]);
        };
    }


    public static function imgManage($dbTable,$data)
    {
        //获取关联信息
        $model = Loader::model($dbTable);
        $sqlStr = preModelStr($data);
        $sqlStr = $sqlStr."select();";
        $info = eval($sqlStr);
        $info = $model->dealGet(resDeal($info));
        if (count($info)==0) {
            throw new ErrorMessage([
                'msg' => '权限不足',
            ]);
        }
        
        $info = $info[0];

        if ($data['FuncName']=="add") {

            if (!empty($info['img_array'])) {
                
                foreach ($info['img_array'] as $value) {
                    
                    $addImg = File::where('id', $value)->update(['relation_id' => $info['id'],'relation_table'=>$dbTable,'relation_status'=>1]);

                }

            }
            
        }elseif ($data['FuncName']=="update") {
            
            if (isset($data['data']['img_array'])) {
                
                foreach ($data['data']['img_array'] as $new_img) {

                    /*判断新增的图片*/
                    if (!in_array($new_img,$info['img_array'])) {
                        
                        $addImg = File::where('id', $new_img)->update(['relation_id' => $info['id'],'relation_table'=>$dbTable,'relation_status'=>1]);

                    }
                    
                }

                foreach ($info['img_array'] as $old_img) {
                    
                    /*判断删除的图片*/
                    if (!in_array($old_img,$data['data']['img_array'])) {
                        
                        $delImg = File::where('id', $old_img)->update(['relation_status'=>-1]);

                    }

                }

            }

        }
    }
}