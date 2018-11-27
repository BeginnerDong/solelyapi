<?php
/**
 * Created by 七月.
 * Author: 七月
 * 微信公号：小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/2/26
 * Time: 16:02
 */

namespace app\api\service\base;


use app\api\model\Order as OrderModel;
use app\api\model\ThirdApp as ThirdappModel;
use app\api\model\User as UserModel;
use app\api\model\UserCoupon as UserCouponModel;
use app\api\model\FlowLog;
use app\api\model\Common as CommonModel;

use app\api\service\UserOrder as OrderService;
use app\api\service\base\WxPay;
use app\api\service\base\CommonService as CommonService;
use app\api\validate\CommonValidate as CommonValidate;
use app\lib\exception\OrderException;
use app\lib\exception\TokenException;
use app\lib\exception\ErrorException;
use app\lib\exception\SuccessException;


use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;
use think\Exception;
use think\Loader;
use think\Log;
use think\Db;
use think\Cache;
//Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');


class Pay
{
    private static $orderNo;
    private static $orderID;
    private static $token;

    function __construct(){
        
    }
    public static function multiPay($data,$inner=false){

        if(!$inner){
            self::$token = $data['token'];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
        };
        $pay_no = makePayNo();
        $price = 0;
        foreach($multiPay as $key => $value){

            $orderInfo =  CommonModel::CommonGet('Order',$data);
            if(count($orderInfo['data'])!=1){
                throw new ErrorMessage([
                    'msg' => '关联订单有误',
                ]);
            };
            if(count($orderInfo['data'])!=1){
                throw new ErrorMessage([
                    'msg' => '关联订单有误',
                ]);
            };
            if(isset($value['wxPay'])&&isset($value['wxPayStatus'])&&$value['wxPayStatus']==0){
                $price += $value['wxPay'];
                $value['pay_no'] = $pay_no;
            };
            $orderInfo = self::checkParamValid($value,$orderInfo,$userInfo);
            
        };

        if($price>0){
            return WxPay::pay($userInfo,$pay_no,$price);
        }else{
            foreach($multiPay as $key => $value){
                self::pay($value);
            };
        };

    }


    public static function pay($data,$inner=false){
        
        if(!$inner){
            self::$token = $data['token'];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
        };

        $orderInfo =  CommonModel::CommonGet('Order',$data);
        if(count($orderInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联订单有误',
            ]);
        };

        $orderInfo = $orderInfo['data'][0];
        self::checkStock($orderInfo);
        if(!$orderInfo['pay_no']&&!isset($data['pay_no'])){
            $data['pay_no'] = makePayNo();
        };
        
        $modelData = [];
        $modelData['searchItem'] = [
            'user_no'=>$orderInfo['user_no']
        ];
        $userInfo =  CommonModel::CommonGet('User',$modelData);
        if(count($userInfo['data'])==0){
            throw new ErrorMessage([
                'msg' => 'userInfo未创建',
            ]);
        };
        $userInfo = $userInfo['data'][0];
        
        $orderInfo = self::checkParamValid($data,$orderInfo,$userInfo);
        if(isset($data['wxPay'])&&isset($data['wxPayStatus'])&&$data['wxPayStatus']==0){            
            return WxPay::pay($userInfo,$orderInfo['pay_no'],$data['wxPay']);
        };
        Db::startTrans();
        try{
            
            if(isset($data['balance'])){
                self::balancePay($userInfo,$orderInfo,$data['balance']);
            };
            if(isset($data['score'])){
                self::scorePay($userInfo,$orderInfo,$data['score']);
            };
            if(isset($data['coupon'])){
                self::couponPay($userInfo,$orderInfo,$data['coupon']);
            };
            if(isset($data['other'])){
                self::otherPay($userInfo,$orderInfo,$data['other']);
            };
            //会员卡支付
            if (isset($data['card'])) {
                self::cardPay($userInfo,$orderInfo,$data['card']);
            };
            
            Db::commit();
        }catch (Exception $ex){
            Db::rollback();
            throw $ex;
        };
        
        $pass = self::checkIsPayAll($data['searchItem']);
        if($pass){
            throw new SuccessMessage([
                'msg' => '支付完成',
            ]);
        }else{
            //self::returnPay();
            throw new SuccessMessage([
                'msg' => '支付成功',
            ]);
        };
        
    }
  

    public static function checkStock($orderInfo)
    {
        
        $modelData = [];
        $modelData['searchItem'] = ['order_no'=>$orderInfo['order_no']];
        $orderItemInfo =  CommonModel::CommonGet('OrderItem',$modelData);
        if(!count($orderItemInfo['data'])>0){
            throw new ErrorMessage([
                'msg' => 'orderItem关联信息有误',
            ]);
        };
        foreach ($orderItemInfo['data'] as $key => $value) {
            $modelData = [];
            
            if(!$value['sku_id']){
                $modelData['searchItem']['id'] = $value['product_id'];
                $product =  CommonModel::CommonGet('Product',$modelData);
            }else{
                $modelData['searchItem']['id'] = $value['sku_id'];
                $product =  CommonModel::CommonGet('Sku',$modelData);
            };
            if(count($product['data'])!=1){
                throw new ErrorMessage([
                    'msg' => 'product关联信息有误',
                ]);
            };
            $product = $product['data'][0];
            if((isset($orderInfo['isGroup'])&&$product['group_stock']<$value['count'])||(!isset($orderInfo['isGroup'])&&$product['stock']<$value['count'])){
                throw new ErrorMessage([
                    'msg' => '库存不足',
                    'info'=>$product
                ]);
                return;
            };
        };
        
    }


    public static function balancePay($userInfo,$orderinfo,$balance)
    {
        
        $modelData = [];
        $modelData['data'] = array(
            'type' => 2,
            'count'=>-$balance,
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>'余额支付',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'余额支付失败'
            ]);
        };
        
    }

    public static function otherPay($userInfo,$orderinfo,$other)
    {
        
        $modelData = [];
        $modelData['data'] = array(
            'type' => 7,
            'count'=>-$other['price'],
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>$other['msg'],
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'other支付失败'
            ]);
        };
        
    }


    public static function scorePay($userInfo,$orderinfo,$score)
    {  

        $modelData = [];
        $modelData['data'] = array(
            'type' => 3,
            'count'=>-$score,
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>'积分支付,积分兑付比率为:'.$userInfo['info']['score_ratio'],
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'积分支付失败'
            ]);
        };
        
    }

    public static function couponPay($userInfo,$orderinfo,$coupon)
    {
        $modelData = [];
        $modelData['searchItem']['order_no'] = $coupon['coupon_no'];
        $modelData['searchItem']['user_no'] = $userInfo['user_no'];
        $couponInfo =  CommonModel::CommonGet('Order',$modelData);
        if(count($couponInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联优惠券有误',
            ]);
        };
        $couponInfo = $couponInfo['data'][0];
        $modelData = [];
        $modelData['FuncName'] = 'add';
        $modelData['data'] = array(
            'type' => $couponInfo['type'],
            'count'=>-$orderinfo['coupon']['price'],
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>'优惠券抵减',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
            'relation_id'=>$couponInfo['order_no'],
        );
        if($couponInfo['type']==3){
            if((isset($couponInfo['standard'])&&($orderinfo['price']<$couponInfo['standard']))||$coupon['price']>$couponInfo['price']){
                throw new ErrorMessage([
                    'msg' => '优惠券使用不合规',
                ]);
            };
        };
        if($couponInfo['type']==4){
            if((isset($couponInfo['standard'])&&($orderinfo['price']<$couponInfo['standard']))||$coupon['price']>$orderinfo['price']*$couponInfo['price']/100){
                throw new ErrorMessage([
                    'msg' => '优惠券使用不合规',
                ]);
            };
        };
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'核销优惠卷失败'
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['order_no'] = $couponInfo['order_no'];
        $modelData['data']['status'] = -1;
        $modelData['FuncName'] = 'update';
        $res =  CommonModel::CommonSave('Order',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'更新用户信息失败'
            ]);
        };
    }



    public static function cardPay($userInfo,$orderinfo,$card)
    {
        $modelData = [];
        $modelData['searchItem']['order_no'] = $card['card_no'];
        $modelData['searchItem']['user_no'] = $userInfo['user_no'];
        $cardInfo =  CommonModel::CommonGet('Order',$modelData);
        if(count($cardInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '会员卡信息有误',
            ]);
        };
        $cardInfo = $cardInfo['data'][0];
        if(($cardInfo['balance']<$card['price'])){
            throw new ErrorMessage([
                'msg' => '会员卡余额不足',
            ]);
        };
        $modelData = [];
        $modelData['FuncName'] = 'add';
        $modelData['data'] = array(
            'type' => 6,//6类型代表会员卡
            'count'=> -$card['price'],
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>'使用会员卡',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
            'relation_id'=>$cardInfo['order_no'],
        );
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'使用会员卡失败'
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['order_no'] = $cardInfo['order_no'];
        $modelData['data']['balance'] = $cardInfo['balance']-$card['price'];
        $modelData['FuncName'] = 'update';
        $res =  CommonModel::CommonSave('Order',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'更新用户信息失败'
            ]);
        };
    }

    public static function checkParamValid($data,$orderInfo,$userInfo)
    {  
        if($orderInfo['pay_status'] == '1'){
            throw new ErrorMessage([
                'msg' => '订单已支付',
            ]);
        };
        
        if(isset($data['balance'])){
            if($userInfo['info']['balance']<$data['balance']){
                throw new ErrorMessage([
                    'msg' => '余额不足',
                ]);
            };
        };
        if(isset($data['score'])){
            
            if(count($userInfo['info'])==0||($userInfo['info']['score']<$data['score']/$userInfo['info']['score_ratio'])){
                throw new ErrorMessage([
                    'msg' => '积分不足',
                ]);
            };
        };
        

        $modelData = [];
        $modelData['searchItem']['id'] = $orderInfo['id'];
        if(isset($data['data'])){
            $modelData['data'] = $data['data'];
        }; 
        if(isset($data['payAfter'])){
            $modelData['data']['payAfter'] = json_encode($data['payAfter']);
        };
        if(isset($data['pay_no'])){
            $modelData['data']['pay_no'] = $data['pay_no'];
            $orderInfo['pay_no'] = $modelData['data']['pay_no'];
        };   
        $modelData['FuncName'] = 'update';
        if($modelData){
            $res =  CommonModel::CommonSave('Order',$modelData);
            if(!$res>0){
                throw new ErrorMessage([
                    'msg'=>'更新OrderPay信息失败'
                ]);
            };
        };
        return $orderInfo;

    }

    public static function checkIsPayAll($searchItem)
    {
        $modelData = [];
        $modelData['searchItem'] = $searchItem;
        $orderInfo = CommonModel::CommonGet('Order',$modelData);
        if(!count($orderInfo['data'])>0){
            throw new ErrorMessage([
                'msg'=>'order信息有误'
            ]);
        };
        $orderInfo = $orderInfo['data'][0];
        $pass = false;

        $totalPrice = 0;
        $modelData = [];
        $modelData['searchItem']['order_no'] = $orderInfo['order_no'];
        $res = CommonModel::CommonGet('FlowLog',$modelData);

        if(count($res['data'])>0){
            foreach ($res['data'] as $key => $value) {
                $totalPrice += $value['count'];
            };
        };
        
        $testNum = -floatval($orderInfo['price']);
        if(bccomp($totalPrice,-floatval($orderInfo['price']),2)==0){
            $pass = true;
        };
        if($pass){
            $modelData = [];
            $modelData['searchItem']['id'] = $orderInfo['id'];
            $modelData['data']['pay_status'] = 1;
            $modelData['data']['update_time'] = time();
            $modelData['FuncName'] = 'update';
            if(!empty($orderinfo['payAfter'])){
                $modelData['saveAfter'] = json_decode($orderinfo['payAfter']);
            }; 
            $res =  CommonModel::CommonSave('Order',$modelData);
        };
        return $pass;

    }





    public static function returnPay($data,$inner=false){
        
        if(!$inner){
            self::$token = $data['token'];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
        };

        $orderInfo =  CommonModel::CommonGet('Order',$data);
        if(count($orderInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联订单有误',
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['order_no'] = $orderInfo['data'][0]['order_no'];
        $FlowLogInfo =  CommonModel::CommonGet('FlowLog',$modelData);
        $FlowLogInfo = $FlowLogInfo['data'];
        if(count($FlowLogInfo)>0){
            foreach ($FlowLogInfo as $key => $value) {     
                $modelData = [];
                $modelData['searchItem']['id'] = $FlowLogInfo[$key]['id'];
                $modelData['data']['status'] = -1;
                $modelData['data']['update_time'] = time();
                $modelData['FuncName'] = 'update';
                $res =  CommonModel::CommonSave('FlowLog',$modelData);   
                if(!$res>0){
                    throw new ErrorMessage([
                        'msg' => '支付撤回失败',
                    ]);
                }; 
            };
            $modelData = [];
            $modelData['searchItem']['id'] = $FlowLogInfo[$key]['id'];
            $modelData['data']['pay_status'] = 0;
            $modelData['data']['order_step'] = 2;
            $modelData['FuncName'] = 'update';
            $res =  CommonModel::CommonSave('Order',$modelData);

            throw new SuccessMessage([
                'msg' => '撤回成功',
            ]);
        }else{
            throw new ErrorMessage([
                'msg' => '重复撤回',
            ]);
        };

    }

    

    

    

    

}