<?php

namespace app\api\model;
use think\Model;
use app\api\model\relation;
use app\api\model\Label;
use app\api\model\labelItem;
use app\api\model\Product;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;



class Sku extends Model{

    public static function dealAdd($data)
    {   
        
        $standard = ['sku_no'=>'','title'=>'','label_array'=>'','sku_item'=>[],'product_id'=>'','stock'=>'','price'=>'','mainImg'=>[],'description'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','thirdapp_id'=>'','status'=>1,'user_no'=>'',];
        

        $res = Product::get(['product_no' => $data['data']['product_no']]);
        $res = resDeal([$res])[0];
        if(!$res){
            throw new ErrorMessage([
                'msg' => '关联信息有误',
            ]);
        };
        
        //return $data;
        Product::where('id', $res['id'])->update(['sku_item' => array_merge($res['sku_item'],$data['data']['sku_item'])]);

        $data['data']['category_id'] = $res['category_id'];
        $data['data']['spu_array'] = $res['spu_array'];
        $data['data']['sku_no'] = makeSkuNo($res);

        $data['data'] = chargeBlank($standard,$data['data']);
        return $data;
        
    }

    public static function dealGet($data)
    {   
        

        $product_array = [];

        foreach ($data as $key => $value) {
            array_push($product_array,$value['product_no']);
        };

        $product = resDeal((new product())
            ->where('product_no','in',$product_array)
            ->select()
        );
        

        foreach ($product as $key => $value) {
            $sku_array = $value['sku_array'];
            array_push($sku_array,$value['category_id']);
            $label = resDeal((new Label())->where('id','in',$sku_array)->whereOr('parentid','in',$sku_array)->select());
            $label = clist_to_tree($label);
            $label = changeIndexArray('product_no',$label);
            $product[$key]['label'] = $label;
        };

        $product = changeIndexArray('product_no',$product);
        

        foreach ($data as $key => $value) {
            if(isset($product[$value['product_no']])){
                $data[$key]['product'] = $product[$value['product_no']];  
            }else{
                $data[$key]['product'] = [];
            };
        };
        
        
        return $data;
        
        
    }

    public static function dealUpdate($data)
    {   

        if(array_key_exists("sku_item",$data['data'])&&!empty($data['data']['sku_item'])){
            $sku = Sku::get($data['searchItem']);
            
            
            $product = resDeal([Product::get(['product_no'=>$sku['product_no']])])[0];
            
            $res = Product::where(['product_no'=>$sku['product_no']])->update(['sku_item' => json_encode(array_merge($product['sku_item'],$data['data']['sku_item']))]);
            if(!$res>0){
                throw new ErrorMessage([
                    'msg' => '关联更新product信息失败',
                ]);
            };
        };
        
     
    }

    public static function dealRealDelete($data)
    {   

        
        
    }


    
}
