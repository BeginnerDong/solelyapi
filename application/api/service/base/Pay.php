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

use app\api\service\beforeModel\Common as BeforeModel;
use app\api\service\func\FlowLog as FlowLogService;
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


class Pay
{
    private static $orderNo;
    private static $orderID;
    private static $token;

    function __construct(){
        
    }


    public static function pay($data,$inner=false){
        
        if(!$inner){
            self::$token = $data['token'];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
        };

        $orderInfo = BeforeModel::CommonGet('Order',$data);

        if(count($orderInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联订单有误',
            ]);
        }else{
            $orderInfo = $orderInfo['data'][0];
            if($orderInfo['type']!=6){
                self::checkStock($orderInfo);
            };
            if(!$orderInfo['pay_no']&&!isset($data['pay_no'])){
                $data['pay_no'] = makePayNo();
            }else if($orderInfo['pay_no']){
                $data['pay_no'] = $orderInfo['pay_no'];
            }
            $modelData = [];
            $modelData['searchItem'] = [
                'user_no'=>$orderInfo['user_no']
            ];
            $userInfo = BeforeModel::CommonGet('User',$modelData);
            if(count($userInfo['data'])==0){
                throw new ErrorMessage([
                    'msg' => 'userInfo未创建',
                ]);
            };
            $userInfo = $userInfo['data'][0];
            $orderInfo = self::checkParamValid($data,$orderInfo,$userInfo);
        };

        if(!isset($data['wxPayStatus'])){
            $data['wxPayStatus'] = 0;
        };
        if(isset($data['wxPay'])&&isset($data['wxPayStatus'])&&$data['wxPayStatus']==0){

            /*判断是否是二次调起支付*/
            $modelData = [];
            $modelData['searchItem']['pay_no'] = $data['pay_no'];
            $payLog = BeforeModel::CommonGet('PayLog',$modelData);
            if (count($payLog['data'])>0) {
                $payLog = $payLog['data'][0];
                if ($payLog['pay_info']['wxPay']['price']!=$data['wxPay']['price']) {
                    $modelData = [];
                    $modelData['FuncName'] = "update";
                    $modelData['searchItem']['id'] = $payLog['id'];
                    $modelData['data']['status'] = -1;
                    $upLog = BeforeModel::CommonSave('PayLog',$modelData);
                    $data['pay_no'] = makePayNo();
                    $modelData = [];
                    $modelData['FuncName'] = "update";
                    $modelData['searchItem']['id'] = $orderInfo['id'];
                    $modelData['data']['pay_no'] = $data['pay_no'];
                    $upOrder = BeforeModel::CommonSave('Order',$modelData);
                }
            }

            /*记录订单的全部信息，回调时执行其它支付方式*/
            $logData['pay_info'] = $data;
            WxPay::pay($userInfo,$data['pay_no'],$data['wxPay']['price'],$logData);

        };

        Db::startTrans();
        try{

            if(isset($data['balance'])){
                self::balancePay($userInfo,$orderInfo,$data['balance'],$data);
            };
            if(isset($data['score'])){
                self::scorePay($userInfo,$orderInfo,$data['score'],$data);
            };
            if(isset($data['coupon'])&&count($data['coupon'])>0){
                foreach ($data['coupon'] as $key => $value) {
                    self::couponPay($userInfo,$orderInfo,$value,$data);
                };
            };
            if(isset($data['other'])){
                self::otherPay($userInfo,$orderInfo,$data['other'],$data);
            };
            //会员卡支付
            if (isset($data['card'])) {
                self::cardPay($userInfo,$orderInfo,$data['card'],$data);
            };
            
            Db::commit();
        }catch (Exception $ex){
            Db::rollback();
            throw $ex;
        };
        throw new SuccessMessage([
            'msg' => '支付完成',
        ]);
    }
  

    public static function checkStock($orderInfo)
    {
        
        $modelData = [];
        $modelData['searchItem'] = ['order_no'=>$orderInfo['order_no']];
        $orderItemInfo = BeforeModel::CommonGet('OrderItem',$modelData);
        if(!count($orderItemInfo['data'])>0){
            throw new ErrorMessage([
                'msg' => 'orderItem关联信息有误',
            ]);
        };
        foreach ($orderItemInfo['data'] as $key => $value) {
            $modelData = [];
            
            if(!$value['sku_id']){
                $modelData['searchItem']['id'] = $value['product_id'];
                $product = BeforeModel::CommonGet('Product',$modelData);
            }else{
                $modelData['searchItem']['id'] = $value['sku_id'];
                $product = BeforeModel::CommonGet('Sku',$modelData);
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


    public static function balancePay($userInfo,$orderinfo,$balance,$data)
    {
        $modelData = [];
        $modelData['data'] = array(
            'type' => 2,
            'count'=>-$balance,
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$data['pay_no'],
            'trade_info'=>'余额支付',
            'relation_table'=>'order',
            'extra_info'=>isset($balance['extra_info'])?isset($balance['extra_info']):'',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);

        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'余额支付失败'
            ]);
        };
        
    }

    public static function otherPay($userInfo,$orderinfo,$other,$data)
    {
        
        $modelData = [];
        $modelData['data'] = array(
            'type' => 7,
            'count'=>-$other['price'],
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$data['pay_no'],
            'trade_info'=>isset($other['msg'])?$other['msg']:'其它',
            'relation_table'=>'order',
            'extra_info'=>isset($other['extra_info'])?isset($other['extra_info']):'',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);
        
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'other支付失败'
            ]);
        };
        
    }


    public static function scorePay($userInfo,$orderinfo,$score,$data)
    {  

        $modelData = [];
        $modelData['data'] = array(
            'type' => 3,
            'count'=>-$score,
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$data['pay_no'],
            'trade_info'=>'积分支付,积分兑付比率为:'.$userInfo['info']['score_ratio'],
            'relation_table'=>'order',
            'extra_info'=>isset($score['extra_info'])?isset($score['extra_info']):'',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
        );
        $modelData['FuncName'] = 'add';
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);

        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'积分支付失败'
            ]);
        };
        
    }

    public static function couponPay($userInfo,$orderinfo,$coupon,$data)
    {
        $modelData = [];
        $modelData['searchItem']['id'] = $coupon['id'];
        $modelData['searchItem']['user_no'] = $userInfo['user_no'];
        $couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
        if(count($couponInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联优惠券有误',
            ]);
        };
        $couponInfo = $couponInfo['data'][0];

        if($couponInfo['type']==1){//抵扣券
            if((($couponInfo['condition']!=0)&&($orderinfo['price']<$couponInfo['condition']))||($couponInfo['invalid_time']>time())||($coupon['price']>$couponInfo['value'])){
                throw new ErrorMessage([
                    'msg' => '优惠券使用不合规',
                ]);
            };
        };
        if($couponInfo['type']==2){//折扣券
            if((($couponInfo['condition']!=0)&&($orderinfo['price']<$couponInfo['condition']))||($couponInfo['invalid_time']>time())||($coupon['price']>$orderinfo['price']*$couponInfo['discount']/100)){
                throw new ErrorMessage([
                    'msg' => '优惠券使用不合规',
                ]);
            };
        };

        //检测使用数量限制
        if ($couponInfo['use_limit']>0) {
            
            $modelData = [];
            $modelData['searchItem']['pay_no'] = $orderinfo['pay_no'];
            $modelData['searchItem']['coupon_no'] = $couponInfo['coupon_no'];
            $modelData['searchItem']['pay_status'] = 1;
            $modelData['searchItem']['use_type'] = 2;
            $coupons = BeforeModel::CommonGet('UserCoupon',$modelData);
            if ($couponInfo['use_limit']<count($coupons['data'])) {
                throw new ErrorMessage([
                    'msg' => '优惠券使用数量超限',
                ]);
            }

        }

        //店铺优惠券检验to do...
        
        $modelData = [];
        $modelData['FuncName'] = 'add';
        $modelData['data'] = array(
            'type' => 4,
            'count'=>-$coupon['price'],
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$data['pay_no'],
            'trade_info'=>'优惠券抵减',
            'relation_table'=>'order',
            'standard_id'=>isset($coupon['standard_id'])?$coupon['standard_id']:'',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
            'relation_id'=>$couponInfo['order_no'],
        );
        
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);


        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'核销优惠卷失败'
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['id'] = $couponInfo['id'];
        $modelData['data']['use_step'] = 2;
        $modelData['FuncName'] = 'update';
        $res = BeforeModel::CommonSave('UserCoupon',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'更新优惠券信息失败'
            ]);
        };
    }



    public static function cardPay($userInfo,$orderinfo,$card,$data)
    {
        $modelData = [];
        $modelData['searchItem']['order_no'] = $card['card_no'];
        $modelData['searchItem']['user_no'] = $userInfo['user_no'];
        $cardInfo = BeforeModel::CommonGet('Order',$modelData);
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
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$data['pay_no'],
            'trade_info'=>'使用会员卡',
            'relation_table'=>'order',
            'extra_info'=>isset($data['card']['extra_info'])?isset($data['card']['extra_info']):'',
            'thirdapp_id'=>$userInfo['thirdapp_id'],
            'user_no'=>$userInfo['user_no'],
            'relation_id'=>$cardInfo['order_no'],
        );
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);

        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'使用会员卡失败'
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['order_no'] = $cardInfo['order_no'];
        $modelData['data']['balance'] = $cardInfo['balance']-$card['price'];
        $modelData['FuncName'] = 'update';
        $res = BeforeModel::CommonSave('Order',$modelData);
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
        $modelData['data'] = [];
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
        if($modelData['data']){
            $res = BeforeModel::CommonSave('Order',$modelData);
            if(!$res>0){
                throw new ErrorMessage([
                    'msg'=>'更新OrderPay信息失败'
                ]);
            };
        };
        return $orderInfo;

    }
    

    public static function returnPay($data,$inner=false){
        
        if(!$inner){
            self::$token = $data['token'];
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
        };

        $orderInfo = BeforeModel::CommonGet('Order',$data);
        if(count($orderInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联订单有误',
            ]);
        };
        $modelData = [];
        $modelData['searchItem']['order_no'] = $orderInfo['data'][0]['order_no'];
        $FlowLogInfo = BeforeModel::CommonGet('FlowLog',$modelData);
        $FlowLogInfo = $FlowLogInfo['data'];
        if(count($FlowLogInfo)>0){
            foreach ($FlowLogInfo as $key => $value) {     
                $modelData = [];
                $modelData['searchItem']['id'] = $FlowLogInfo[$key]['id'];
                $modelData['data']['status'] = -1;
                $modelData['data']['update_time'] = time();
                $modelData['FuncName'] = 'update';
                $res = BeforeModel::CommonSave('FlowLog',$modelData);   
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
            $res = BeforeModel::CommonSave('Order',$modelData);

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