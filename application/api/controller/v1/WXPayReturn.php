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

use app\api\model\Common as CommonModel;
use app\api\model\FlowLog;
use app\api\model\Log;
use app\api\service\base\Pay as PayService;


use think\Request as Request;
use app\lib\exception\TokenException;



class WXPayReturn extends Controller
{
    //支付回调
    public function receiveNotify(){
        //$data = Request::instance()->param();
        $xmlData = file_get_contents('php://input');
        $data = xml2array($xmlData);
        //开始支付回调逻辑....
        if($data['RESULT_CODE']=='SUCCESS'){

            $orderId = $data['OUT_TRADE_NO'];
            $logInfo = resDeal([Log::get(['pay_no'=>$orderId])])[0];
            if($logInfo&&$logInfo['transaction_id']==$data['TRANSACTION_ID']){
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
                'update_time'=>time(),
            );
            if($logInfo['payAfter']){
                $modelData['payAfter'] = $logInfo['payAfter'];
            };
            $modelData['FuncName'] = 'update';
            $saveLog =  CommonModel::CommonSave('Log',$modelData);
            if($logInfo['behavior']==1){
                return true;
            };
            $TOTAL_FEE = $data['TOTAL_FEE']/100;
            $modelData = [];
            $modelData['searchItem']['pay_no'] = $orderId;
            $orderList =  CommonModel::CommonGet('Order',$modelData);
            if(!count($orderList['data'])>0){
                throw new ErrorMessage([
                    'msg' => '关联订单有误',
                ]);
                return;
            };
            
            foreach ($orderList['data'] as $key => $value) {
                $TOTAL_FEE = $this->dealOrder($data,$value,$TOTAL_FEE);
            };
            
            //根据订单号查询订单信息
                 
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
            $saveLog =  CommonModel::CommonSave('Log',$modelData);
        }
    }


    public function dealOrder($data,$orderinfo,$TOTAL_FEE){
        
        $modelData = [];
        $modelData['data'] = array(
            'type' => 1,
            'count'=>$orderinfo['pay']['wxPay'],
            'order_no'=>$orderinfo['order_no'],
            'trade_info'=>'微信支付',
            'thirdapp_id'=>$orderinfo['thirdapp_id'],
            'user_no'=>$orderinfo['user_no'],
            'payInfo'=>[
                'appid'=>$data['APPID'],
                'mch_id'=>$data['MCH_ID'],
                'transaction_id'=>$data['TRANSACTION_ID'],
                'out_trade_no'=>$data['OUT_TRADE_NO'],
            ]
        );
        $modelData['FuncName'] = 'add';
        if(!empty($orderinfo['payAfter'])){
            $modelData['saveAfter'] = json_decode($orderinfo['payAfter']);
        }; 

        //return $modelData;
        $res =  CommonModel::CommonSave('FlowLog',$modelData);
        //return $res;

        $modelData = [];
        $modelData = $orderinfo['pay'];
        $modelData['searchItem'] = [
            'id'=>$orderinfo['id']
        ];
        $modelData['wxPayStatus'] = 1;
        PayService::pay($modelData,true);
        return $TOTAL_FEE - $orderinfo['pay']['wxPay'];
    }

    
}