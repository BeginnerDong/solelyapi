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
use app\api\model\ThirdApp;
use app\api\model\Log;

use app\api\service\beforeModel\Common as BeforeModel;

use think\Exception;
use think\Loader;
use think\Cache;

use app\lib\exception\ErrorMessage;
use app\lib\exception\SuccessMessage;
use app\api\validate\CommonValidate as CommonValidate;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');


class WxPay
{
    private $orderNo;
    private $orderID;

    function __construct()
    {
        
    }


    // 构建微信支付订单信息
    public static function directPay($data,$inner=false)
    {   

        //user相关
        $modelData = [];
        if(!$inner){
            (new CommonValidate())->goCheck('one',$data);
            checkTokenAndScope($data,config('scope.two')); 
            $modelData['searchItem'] = [
                'user_no'=>Cache::get($data['token'])['user_no']
            ];
        }else{
            $modelData['searchItem'] = [
                'user_no'=>Cache::get($data['token'])['user_no']
            ];
        };
        $userInfo = BeforeModel::CommonGet('User',$modelData);
        if(count($userInfo['data'])==0){
            throw new ErrorMessage([
                'msg' => 'userInfo未创建',
            ]);
        };
        $userInfo = $userInfo['data'][0];
        if(isset($data['openid'])){
            $userInfo['openid'] = $data['openid'];
        };
        $pay_no = makePayNo();
        $logData = ['behavior'=>1];
        if(isset($data['data'])){
            $logData = array_merge($logData,$data['data']);
        };
        
        return self::pay($userInfo,$pay_no,$data['wxPay'],$logData);

    }

    
    // 构建微信支付订单信息
    public static function pay($userInfo,$pay_no,$price,$logData=[])
    {
        
        $thirdappinfo = ThirdApp::get(['id' => $userInfo['thirdapp_id']]);
        $wxOrderData = new \WxPayUnifiedOrder();
        $wxOrderData->SetOut_trade_no($pay_no);
        $wxOrderData->SetTrade_type('JSAPI');
        $wxOrderData->SetTotal_fee($price*100);
        $wxOrderData->SetBody('solelyService');
        $wxOrderData->SetOpenid($userInfo['openid']);
        $wxOrderData->SetNotify_url(config('secure.pay_back_url'));
        return self::getPaySignature($wxOrderData,$thirdappinfo,$pay_no,$userInfo,$logData);
        
    }

    //向微信请求订单号并生成签名
    private static function getPaySignature($wxOrderData,$thirdappinfo,$pay_no,$userInfo,$logData)
    {
        
        $wxOrder = \WxPayApi::unifiedOrder($wxOrderData,$thirdappinfo);
        
        if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] !='SUCCESS'){
            throw new ErrorMessage([
                'msg'=>$wxOrder['return_msg'],
                'info'=>$wxOrder
            ]);
        };
        
        if($thirdappinfo['wx_appid']){
            $signature = self::sign($wxOrder,$thirdappinfo['wx_appid'],$thirdappinfo['wxkey']);
        }else{
            $signature = self::sign($wxOrder,$thirdappinfo['appid'],$thirdappinfo['wxkey']);
        };
        
        if($pay_no){
            OrderModel::where('pay_no', $pay_no)->update([
                'prepay_id' => $wxOrder['prepay_id'],
                'wx_prepay_info' => json_encode($signature),
            ]);
            $modelData = [];
            if($logData){
                $modelData['data'] = array_merge(array(
                    'title'=>'微信支付',
                    'pay_no'=>$pay_no,
                    'prepay_id'=>$wxOrder['prepay_id'],
                    'create_time'=>time(),
                    'type'=>2,
                    'user_no'=>$userInfo['user_no'],
                    'thirdapp_id'=>$thirdappinfo['id'],
                ),$logData);
            }else{
                $modelData['data'] = array(
                    'title'=>'微信支付',
                    'pay_no'=>$pay_no,
                    'prepay_id'=>$wxOrder['prepay_id'],
                    'create_time'=>time(),
                    'type'=>2,
                    'user_no'=>$userInfo['user_no'],
                    'thirdapp_id'=>$thirdappinfo['id'],
                ); 
            };
            $modelData['FuncName'] = 'add';
            $saveLog = BeforeModel::CommonSave('Log',$modelData);
        };
        
        throw new SuccessMessage([
            'msg'=>'微信支付发起成功',
            'info'=>$signature
        ]);
        
    }

    

    // 签名
    private static function sign($wxOrder,$appid,$key)
    {
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid($appid);
        $jsApiPayData->SetTimeStamp((string)time());
        $rand = md5(time() . mt_rand(0, 1000));
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('prepay_id='.$wxOrder['prepay_id']);
        $jsApiPayData->SetSignType('MD5');
        $sign = $jsApiPayData->MakeSignByThird($key);
        $rawValues = $jsApiPayData->GetValues();
        $rawValues['paySign'] = $sign;
        return $rawValues;
    }


    //关闭微信订单
    public static function closeorder($orderinfo)
    {
        //获取项目信息
        $thirdappinfo = ThirdappModel::getThirdUserInfo($orderinfo['thirdapp_id']);
        $wxOrderClose = new \WxPayCloseOrder();
        $wxOrderClose->SetOut_trade_no($orderinfo['order_no']);
        $wxOrder = \WxPayApi::closeOrder($wxOrderClose,$thirdappinfo);
        return $wxOrder;
    }


    //订单退款
    public static function refundOrder($flowLogID){


        $modelData = [];
        $modelData['searchItem'] = ['id'=>$flowLogID];
        $FlowLogInfo = BeforeModel::CommonGet('FlowLog',$modelData);
        if(count($FlowLogInfo['data'])!=1){
            throw new ErrorMessage([
                'msg' => '关联订单有误',
            ]);
        };
        $FlowLogInfo = $FlowLogInfo['data'][0];
        $thirdappinfo = ThirdApp::get(['id' => $FlowLogInfo['thirdapp_id']]);
        if(!isset($FlowLogInfo['payInfo']['refund_no'])){
            $refundNo = makePayNo();
            $FlowLogInfo['payInfo']['refund_no'] = $refundNo; 
            $modelData = [];
            $modelData['searchItem'] = ['id'=>$flowLogID];
            $modelData['data'] = ['payInfo'=>$FlowLogInfo['payInfo']];
            $modelData['FuncName'] = 'update';
            $res = BeforeModel::CommonSave('FlowLog',$modelData);
        }else{
            $refundNo = $FlowLogInfo['payInfo']['refund_no'];
        };

        $wxOrderRefund = new \WxPayRefund();
        $wxOrderRefund->SetTransaction_id($FlowLogInfo['payInfo']['transaction_id']);
        $wxOrderRefund->SetOut_trade_no($FlowLogInfo['payInfo']['out_trade_no']);
        $wxOrderRefund->SetOut_refund_no($refundNo);
        $wxOrderRefund->SetTotal_fee(-$FlowLogInfo['count']*100);
        $wxOrderRefund->SetRefund_fee(-$FlowLogInfo['count']*100);
        $wxOrderRefund->SetOp_user_id($FlowLogInfo['payInfo']['mch_id']);
        $wxOrder = \WxPayApi::refund($wxOrderRefund,$thirdappinfo);

        return $wxOrder;
        
    }


    public static function checkParamValid($data,$orderInfo,$userInfo)
    {  
        if($orderInfo['pay_status'] == '1'){
            throw new ErrorMessage([
                'msg' => '订单已支付',
            ]);
        };
        $totalPrice  = 0;
        $pay = [];
        if(isset($data['balance'])){
            if($userInfo['balance']<$data['balance']){
                throw new ErrorMessage([
                    'msg' => '余额不足',
                ]);
            };
            $totalPrice  += $data['balance'];
            $pay['balance'] = $data['balance'];
        };
        if(isset($data['score'])){
            
            if(count($userInfo['info'])==0||($userInfo['info']['score']<$data['score']/$userInfo['info']['score_ratio'])){
                throw new ErrorMessage([
                    'msg' => '积分不足',
                ]);
            };
            $totalPrice  += $data['score'];
            $pay['score'] = $data['score'];

        };
        if(isset($data['coupon'])){
            $totalPrice  += $data['coupon']['price'];
            $pay['coupon'] = $data['coupon'];
        };
        if(isset($data['wxPay'])){
            $totalPrice  += $data['wxPay'];
            $pay['wxPay'] = $data['wxPay'];
        };
        if($totalPrice!=$orderInfo['price']){
            throw new ErrorMessage([
                'msg' => '传递支付参数有误',
            ]);
        };

        $modelData = [];
        $modelData['searchItem']['id'] = $orderInfo['id'];
        if(isset($data['data'])){
            $modelData['data'] = $data['data'];
        }; 
        $modelData['data']['pay'] =$pay;
        if(isset($data['payAfter'])){
            $modelData['data']['payAfter'] = json_encode($data['payAfter']);
        };   
        $modelData['FuncName'] = 'update';
        $res = BeforeModel::CommonSave('Order',$modelData);
        if(!$res>0){
            throw new ErrorMessage([
                'msg'=>'更新OrderPay信息失败'
            ]);
        };

    }


}