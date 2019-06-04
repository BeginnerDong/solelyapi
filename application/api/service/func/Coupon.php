<?php
namespace app\api\service\func;
use app\api\model\Common as CommonModel;
use think\Exception;
use think\Model;
use think\Cache;

use app\api\service\base\Common as CommonService;
use app\api\service\base\CouponPay as PayService;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Coupon{

 
    function __construct($data){
        
    }


    public static function addCoupon($data){

        (new CommonValidate())->goCheck('one',$data);

        $modelData = [];
        $modelData = self::createCouponDate($data);
        $modelData['FuncName'] = 'add';
        $couponRes = CommonModel::CommonSave('UserCoupon',$modelData);

        if($couponRes>0){
            if(isset($data['pay'])){
                $data['pay']['searchItem'] = [
                    'id'=>$couponRes
                ];
                return PayService::couponPay($data['pay'],true);
            }else{
                throw new SuccessMessage([
                    'msg'=>'领取成功',
                    'info'=>[
                        'id'=>$couponRes
                    ]      
                ]);
            }; 
        }else{
            throw new ErrorMessage([
                'msg' => '领取失败',
            ]);
        };
        
    }

    public static function createCouponDate($data){
        if(!isset($data['data'])){
            $data['data'] = [];
        };
        $data = checkTokenAndScope($data,config('scope.two'));
        $modelData = [];
        $modelData = [
            'searchItem'=>[
                'user_no'=>$data['data']['user_no']
            ],
        ];
        $user = CommonModel::CommonGet('User',$modelData);
        if(!count($user['data'])>0){
            throw new ErrorMessage([
                'msg' => '用户不存在',
            ]);
        };
        $user = $user['data'][0];
        if($user['user_type']>1){
            throw new ErrorMessage([
                'msg' => '用户类型不符',
            ]);
        };

        if(!isset($data['coupon_id'])){
            throw new ErrorMessage([
                'msg' => '没有优惠券信息',
            ]);
        };

        $modelData = [];
        $modelData = [
            'searchItem'=>[
                'id'=>$data['coupon_id']
            ],
        ];
        $couponInfo = CommonModel::CommonGet('Coupon',$modelData);
        if(!count($couponInfo['data'])>0){
            throw new ErrorMessage([
                'msg' => '优惠券不存在',
            ]);
        };
        $couponInfo = $couponInfo['data'][0];

        $totalPrice = 0;
        $totalPrice = self::checkAndReduceStock($data['coupon_id'],1,$couponInfo,$totalPrice,$user);
        
        $modelData = [];
        $modelData['data']['coupon_no'] = $couponInfo['coupon_no'];
        $modelData['data']['type'] = $couponInfo['type'];
        $modelData['data']['price'] = $totalPrice;
        $modelData['data']['value'] = $couponInfo['value'];
        $modelData['data']['discount'] = $couponInfo['discount'];
        $modelData['data']['condition'] = $couponInfo['condition'];
        $modelData['data']['invalid_time'] = time()*1000+$couponInfo['valid_time'];
        $modelData['data']['use_limit'] = $couponInfo['use_limit'];
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        $modelData['data']['snap_coupon'] = json_encode($couponInfo);

        if(isset($data['data'])){
            $modelData['data'] = array_merge($data['data'],$modelData['data']);
        };
        return $modelData;
    }


    public static function checkAndReduceStock($coupon_id,$count,$couponInfo,$totalPrice,$user)
    {   

        if($couponInfo['stock']<$count){
            throw new ErrorMessage([
                'msg' => '库存不足',
                'info'=>$couponInfo
            ]);
        };

        if($couponInfo['limit']>0){
            $modelData = [];
            $modelData['searchItem']['coupon_no'] = $couponInfo['coupon_no'];
            $modelData['searchItem']['user_no'] = $user['user_no'];
            $limit = CommonModel::CommonGet('UserCoupon',$modelData);
            if(count($limit['data'])>=$couponInfo['limit']){
                throw new ErrorMessage([
                    'msg' => '购买数量超限',
                ]);
            };
        };

        return $couponInfo['price']*$count; 
        
    }
}