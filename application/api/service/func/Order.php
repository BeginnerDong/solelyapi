<?php
namespace app\api\service\func;
use app\api\model\Common as CommonModel;
use think\Exception;
use think\Model;
use think\Cache;

use app\api\service\base\Common as CommonService;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Order{

 
    function __construct($data){
        
    }


    public static function addOrder($data){

        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,config('scope.two'));
        $user = Cache::get($data['token']);
        if($user['user_type']>1){
            throw new ErrorMessage([
                'msg' => '用户类型不符',
            ]);
        };
        $totalPrice = 0;
        $type = 0;
        $order_no = makeOrderNo();
        if(isset($data['product'])){
            foreach ($data['product'] as $key => $value) {
                $totalPrice += self::checkAndReduceStock($value,$totalPrice,$type,$order_no,$user);
            }; 
        }else if(isset($data['sku'])){
            foreach ($data['sku'] as $key => $value) {
                $totalPrice += self::checkAndReduceStock($value,$totalPrice,$type,$order_no,$user,true);
            }; 
        };
        
        $modelData = [];
        $modelData['data']['order_no'] = $order_no;
        $modelData['data']['product_type'] = $type;
        $modelData['data']['price'] = $totalPrice;
        $modelData['data']['type'] = $data['type'];
        $modelData['data']['deadline'] = isset($data['deadline'])?$data['deadline']:'';
        $modelData['data']['pay'] = json_encode($data['pay']);
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        $modelData['data']['snap_address'] = isset($data['snap_address'])?$data['snap_address']:'';
        $modelData['data']['passage1'] = isset($data['passage1'])?$data['passage1']:'';
        $modelData['data']['passage2'] = isset($data['passage2'])?$data['passage2']:'';

        //判断是否是团购商品
        if(isset($data['isGroup'])&&!isset($data['group_no'])){
            $modelData['data']['group_no'] = makeGroupNo();
            $modelData['data']['status'] = isset($data['isGroup'])?0:1;
            $modelData['data']['standard'] = isset($data['standard'])?$data['standard']:'';
        }else if(isset($data['isGroup'])&&isset($data['group_no'])){
            $c_modelData = [];
            $c_modelData['searchItem'] = [
                'group_no'=>$data['group_no']
            ];
            $groupRes =  CommonModel::CommonGet('Order',$c_modelData);
            if(count($groupRes['data'])>0){
                $modelData['data']['group_no'] = $data['group_no'];
                $modelData['data']['standard'] = $groupRes['data'][0]['standard'];
                if(count($groupRes['data'])<$groupRes['data'][0]['standard']-1){
                    $modelData['data']['status'] = 0;
                }else{
                    $modelData['data']['status'] = 1;
                    $cc_modelData = [];
                    $cc_modelData['searchItem'] = [
                        'group_no'=>$data['group_no']
                    ];
                    $cc_modelData['data'] = [
                        'status'=>1
                    ];
                    $modelData['FuncName'] = 'update';
                    $groupOrderRes =  CommonModel::CommonSave('Order',$modelData);
                    if(!$groupOrderRes>0){
                        throw new ErrorMessage([
                            'msg' => 'group更新状态失效',
                        ]);
                    };
                };
            }else{
                throw new ErrorMessage([
                    'msg' => 'group_no不存在',
                ]); 
            };
        };

        $modelData['FuncName'] = 'add';
        $orderRes =  CommonModel::CommonSave('Order',$modelData);
        if($orderRes>0){
            throw new SuccessMessage([
                'msg'=>'下单成功',
                'info'=>[
                    'id'=>$orderRes
                ]      
            ]); 
        }else{
            throw new ErrorMessage([
                'msg' => 'group更新状态失效',
            ]);
        };
        
    }

    public static function checkAndReduceStock($data,$totalPrice,$type,$order_no,$user,$isSku=false){

        $modelData = [];
        $modelData['searchItem']['id'] = $data['id'];
        if(!$isSku){
            $product =  CommonModel::CommonGet('Product',$modelData);
        }else{
            $product =  CommonModel::CommonGet('Sku',$modelData);
        };
        if(!count($product['data'])>0){
            throw new ErrorMessage([
                'msg' => '产品不存在或已下架',
                'info'=>$product
            ]); 
        };
        $product = $product['data'][0];
        if($isSku){
            $product['type'] = $product['product']['type'];
        };
        if($type==0){
            $type = $product['type'];
        }else{
            if($type!=$product['type']){
                throw new ErrorMessage([
                    'msg' => '产品类型不匹配',
                    'info'=>$product
                ]);
            }
        };
        
        $modelData = [];
        $modelData['searchItem']['id'] = $product['id'];
        if(isset($data['isGroup'])&&$product['group_stock']>$data['count']){
            $modelData['searchItem']['group_stock'] = $product['group_stock'];
            $modelData['data']['group_stock'] = $product['group_stock']-$data['count'];  
        }else if($product['stock']>$data['count']){
            $modelData['searchItem']['stock'] = $product['stock'];
            $modelData['data']['stock'] = $product['stock']-$data['count'];  
        }else{
            throw new ErrorMessage([
                'msg' => '库存不足',
                'info'=>$product
            ]);
        };
        
        $modelData['FuncName'] = 'update';
        if(!$isSku){
            $res =  CommonModel::CommonSave('Product',$modelData); 
        }else{
            $res =  CommonModel::CommonSave('Sku',$modelData); 
        };
        if(!$res>0){
            self::checkAndReduceStock($data,$totalPrice,$type,$order_no,$user);
        };
        $modelData = [];
        $modelData['data']['order_no'] = $order_no;
        $modelData['data']['product_id'] = $product['id'];
        $modelData['data']['count'] = $data['count'];
        $modelData['data']['snap_product'] = json_encode($product);
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        $modelData['FuncName'] = 'add';
        $orderItemRes =  CommonModel::CommonSave('OrderItem',$modelData);
        if(!$orderItemRes>0){
            throw new ErrorMessage([
                'msg' => '写入产品失败',
                'info'=>$product
            ]);  
        };
        return $product['price']*$data['count']; 
        
    }


    public static function computePrice($data){
        
        $price = (isset($data['balance'])?$data['balance']:0)+ (isset($data['score'])?floatval($data['score']):0) + (isset($data['wx_pay'])?$data['wx_pay']:0);
        if(isset($data['coupon'])){
            foreach ($data['coupon'] as $key => $value) {
                $modelData = [];
                $modelData['searchItem']['id'] = $value;
                $coupon =  CommonModel::CommonGet('Product',$modelData);
                if($coupon['type'] == 3){
                    $price += $coupon['discount'];
                }else if($coupon['type'] == 4){
                    $price = $price*100/$coupon['discount'];
                };
            };
        };
        return $price;

    }


}