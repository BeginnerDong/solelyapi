<?php
/**
 * Created by wjm.
 * Author: wjm
 * Date: 2018/7/13
 * Time: 16:41
 */

namespace app\api\controller\v1;

use think\Db;
use think\Controller;
use app\api\model\FlowLog;
use app\api\model\PayLog;
use app\api\service\base\Pay as PayService;
use app\api\service\base\CouponPay as CouponPayService;
use app\api\service\func\FlowLog as FlowLogService;
use app\api\service\beforeModel\Common as BeforeModel;
use think\Request as Request;
use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

class WXPayReturn extends Controller
{
    //支付回调
    public function receiveNotify(){

        // $data = Request::instance()->param();
        $xmlData = file_get_contents('php://input');
        $data = xml2array($xmlData);
        //开始支付回调逻辑....
        if($data['RESULT_CODE']=='SUCCESS'){

            $orderId = $data['OUT_TRADE_NO'];
            $payLog = resDeal([PayLog::get(['pay_no'=>$orderId])])[0];
            if($payLog&&$payLog['transaction_id']==$data['TRANSACTION_ID']){
                return true;
            };
            
            $modelData = [];
            $modelData['searchItem'] = [
                'pay_no'=>$orderId
            ];

            $modelData['data'] = array(
                'title'=>'微信支付回调成功',
                'transaction_id'=>$data['TRANSACTION_ID'],
                'content'=>$data,
                'pay_no'=>$orderId,
                'update_time'=>time(),
            );
            $modelData['FuncName'] = 'update';
            $saveLog = BeforeModel::CommonSave('PayLog',$modelData);

            if($payLog['behavior']==1){
                return true;
            };
            $TOTAL_FEE = $data['TOTAL_FEE']/100;

            if (isset($payLog['pay_info'])&&isset($payLog['pay_info']['iscoupon'])&&!empty($payLog['pay_info']['iscoupon'])) {
                $modelData = [];
                $modelData['searchItem']['pay_no'] = $orderId;
                $couponInfo = BeforeModel::CommonGet('UserCoupon',$modelData);
                $this->dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog);
                return true;
            }

            $modelData = [];
            $modelData['searchItem']['pay_no'] = $orderId;
            $orderList = BeforeModel::CommonGet('Order',$modelData);
            if(!count($orderList['data'])>0){
                $orderinfo = [];
                $this->dealOrder($data,$orderinfo,$TOTAL_FEE,$payLog);
            }else{
                foreach ($orderList['data'] as $key => $value) {
                    $this->dealOrder($data,$value,$TOTAL_FEE,$payLog);
                };
            }
            return true;

        }else{

            //记录微信支付回调日志
            $modelData = [];
            $modelData['data'] = array(
                'title'=>'微信支付',
                'result'=>$data['RESULT_CODE'],
                'content'=>$data['RETURN_MSG'],
                'pay_no'=>$data['OUT_TRADE_NO'],
                'create_time'=>time(),
                'type'=>2,
            );
            $saveLog = BeforeModel::CommonSave('Log',$modelData);
        }
    }


    public function dealOrder($data,$orderinfo,$TOTAL_FEE,$payLog){
        
        //记录子订单支付信息
        if (isset($payLog['pay_info'])&&isset($payLog['pay_info']['wxPay'])&&isset($payLog['pay_info']['wxPay']['extra_info'])) {
            $extra_info = $payLog['pay_info']['wxPay']['extra_info'];
        }else{
            $extra_info = '';
        }

        $modelData = [];
        $modelData['data'] = array(
            'type' => 1,
            'count'=>-$TOTAL_FEE,
            'order_no'=>isset($orderinfo['order_no'])?$orderinfo['order_no']:'',
            'pay_no'=>$payLog['pay_no'],
            'trade_info'=>'微信支付',
            'thirdapp_id'=>$payLog['thirdapp_id'],
            'user_no'=>$payLog['user_no'],
            'relation_table'=>'order',
            'extra_info'=>$extra_info,
            'payInfo'=>[
                'appid'=>$data['APPID'],
                'mch_id'=>$data['MCH_ID'],
                'transaction_id'=>$data['TRANSACTION_ID'],
                'out_trade_no'=>$data['OUT_TRADE_NO'],
            ]
        );

        $modelData['FuncName'] = 'add';

        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = [];
        $modelData['searchItem']['id'] = $res;
        FlowLogService::checkIsPayAll($modelData);

        $modelData = [];
        $modelData = $payLog;
        //取出第一次调取支付时记录的信息
        $modelData = array_merge($modelData,$payLog['pay_info']);
        $modelData['wxPayStatus'] = 1;
        $modelData['searchItem'] = [
            'pay_no'=>$payLog['pay_no']
        ];
        unset($modelData['pay_info']);

        PayService::pay($modelData,true);
    }

    public function dealCoupon($data,$couponInfo,$TOTAL_FEE,$payLog){

        $modelData = [];
        $modelData['data'] = array(
            'type' => 1,
            'count'=>-$TOTAL_FEE,
            'pay_no'=>$payLog['pay_no'],
            'trade_info'=>'微信支付',
            'thirdapp_id'=>$payLog['thirdapp_id'],
            'user_no'=>$payLog['user_no'],
            'relation_table'=>'coupon',
            'payInfo'=>[
                'appid'=>$data['APPID'],
                'mch_id'=>$data['MCH_ID'],
                'transaction_id'=>$data['TRANSACTION_ID'],
                'out_trade_no'=>$data['OUT_TRADE_NO'],
            ]
        );

        $modelData['FuncName'] = 'add';
        $res = BeforeModel::CommonSave('FlowLog',$modelData);

        $modelData = $payLog;
        //取出第一次调取支付时记录的信息
        $modelData = array_merge($modelData,$payLog['pay_info']);
        $modelData['wxPayStatus'] = 1;
        $modelData['searchItem'] = [
            'pay_no'=>$payLog['pay_no']
        ];
        unset($modelData['pay_info']);

        CouponPayService::pay($modelData,true);

    }
}