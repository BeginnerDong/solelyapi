<?php
namespace app\api\service\func;
use app\api\service\beforeModel\Common as BeforeModel;
use think\Exception;
use think\Model;
use think\Cache;

use app\api\service\base\Common as CommonService;
use app\api\service\base\Pay as PayService;
use app\api\validate\CommonValidate;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Order{

 
    function __construct($data){
        
    }

    public static function addVirtualOrder($data)
	{
        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,config('scope.two'));

        $modelData = [];
        $modelData = self::createVirtualOrderData($data);
        $modelData['FuncName'] = 'add';
        $orderRes = BeforeModel::CommonSave('Order',$modelData);
        if($orderRes>0){
            if(isset($data['pay'])){
                $pay = $data['pay'];
                $pay['searchItem'] = ['id'=>$orderRes];
                return PayService::pay($pay,true);
            }else{
                throw new SuccessMessage([
                    'msg'=>'下单成功',
                    'info'=>[
                        'id'=>$orderRes
                    ]      
                ]);
            };
        }else{
            throw new ErrorMessage([
                'msg' => '下单失败',
            ]);
        };
    }


    public static function addOrder($data)
    {

        (new CommonValidate())->goCheck('one',$data);
		$info = [];

		/*生成父级订单*/
		if(isset($data['parent']){
			$parent_no = makeOrderNo();
			$orderInfo['order_no'] = $parent_no;
			$orderInfo['token'] = $data['token'];
			$orderInfo['parent'] = 1;
			$modelData = [];
			$modelData = self::createVirtualOrderData($orderInfo);
			$modelData['FuncName'] = 'add';
			$parentOrder = BeforeModel::CommonSave('Order',$modelData);
			$data['data']['parent'] = 2;
			$data['data']['parent_no'] = $parent_no;
			$info['parent_id'] = $parentOrder;
		};

		$modelData = [];
		$modelData = self::createOrderData($data);
		$modelData['FuncName'] = 'add';
		$orderRes = BeforeModel::CommonSave('Order',$modelData);
		$info['id'] = $orderRes;

		if($orderRes>0){
			if(isset($data['pay'])){
				$data['pay']['searchItem'] = [
					'id'=>$orderRes
				];
				return PayService::pay($data['pay'],true);
			}else{
				throw new SuccessMessage([
					'msg'=>'下单成功',
					'info'=>$info,
				]);
			}; 
		}else{
			throw new ErrorMessage([
				'msg' => '下单失败',
			]);
		};

    }



    public static function createOrderData($data)
	{
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
        $user = BeforeModel::CommonGet('User',$modelData);
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
        $totalPrice = 0;
        $type = isset($data['type'])?$data['type']:1;
        $order_no = makeOrderNo();

        if (!isset($data['product'])&&!isset($data['sku'])) {
            throw new ErrorMessage([
                'msg' => '购买商品信息错误',
            ]);
        }
        if(isset($data['product'])){
            foreach ($data['product'] as $key => $value) {
                $newArray = self::checkAndReduceStock($value,$totalPrice,$type,$order_no,$user);
                $totalPrice += $newArray['totalPrice'];
            }; 
        }else if(isset($data['sku'])){
            foreach ($data['sku'] as $key => $value) {
                $newArray = self::checkAndReduceStock($value,$totalPrice,$type,$order_no,$user,true);
                $totalPrice += $newArray['totalPrice'];
            }; 
        };

        $modelData = [];
        $modelData['data']['order_no'] = $order_no;
        $modelData['data']['price'] = $totalPrice;
        $modelData['data']['type'] = $type;
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        $modelData['data']['parentid'] = isset($data['parentid'])?$data['parentid']:0;
        $modelData['data']['snap_address'] = isset($data['snap_address'])?$data['snap_address']:'';

        if(isset($data['data'])){
            $modelData['data'] = array_merge($data['data'],$modelData['data']);
        };

        //判断是否是团购商品
        if(isset($data['isGroup'])&&!isset($data['group_no'])){
            $modelData['data']['group_no'] = makeGroupNo();
            $modelData['data']['group_leader'] = "true";
            $modelData['data']['order_step'] = 4;
            $modelData['data']['standard'] = isset($data['data']['standard'])?$data['data']['standard']:'';
        }else if(isset($data['isGroup'])&&isset($data['group_no'])) {
            $c_modelData = [];
            $c_modelData['searchItem'] = [
                'group_no'=>$data['group_no']
            ];
            $groupRes = BeforeModel::CommonGet('Order',$c_modelData);
            if (count($groupRes['data'])>0) {
                $modelData['data']['group_no'] = $data['group_no'];
                $modelData['data']['standard'] = $groupRes['data'][0]['standard'];
                $modelData['data']['order_step'] = 4;
            }else{
                throw new ErrorMessage([
                    'msg' => 'group_no不存在',
                ]); 
            };
        }
        return $modelData;
    }



    public static function createVirtualOrderData($data)
	{
        $user = Cache::get($data['token']);
        if($user['user_type']>1){
            throw new ErrorMessage([
                'msg' => '用户类型不符',
            ]);
        };
        $modelData = [];
        $modelData['data'] = [];
        if(isset($data['data'])){
            $modelData['data'] = array_merge($data['data'],$modelData['data']);
        };
        $order_no = isset($data['order_no'])?$data['order_no']:makeOrderNo();
        
        $modelData['data']['order_no'] = $order_no;
        $modelData['data']['type'] = 4;
        $modelData['data']['pay'] = isset($data['pay'])?json_encode($data['pay']):json_encode([]);
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        return $modelData;
    }



    public static function checkAndReduceStock($data,$totalPrice,$type,$order_no,$user,$isSku=false)
	{

        $modelData = [];
        $modelData['searchItem']['id'] = $data['id'];
        if(!$isSku){
            $product = BeforeModel::CommonGet('Product',$modelData);
        }else{
            $product = BeforeModel::CommonGet('Sku',$modelData);
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
            };
        };

        if((isset($data['isGroup'])&&$product['group_stock']<$data['count'])||(!isset($data['isGroup'])&&$product['stock']<$data['count'])){
            throw new ErrorMessage([
                'msg' => '库存不足',
                'info'=>$product
            ]);
        };

        if($product['limit']>0){
            $modelData = [
                'searchItem'=>[]
            ];
            if(!$isSku){
                $modelData['searchItem']['product_id'] = $data['id'];
            }else{
                $modelData['searchItem']['sku_id'] = $data['id'];
            };
            $modelData['searchItem']['user_no'] = $user['user_no'];
            $modelData['searchItem']['pay_status'] = 1;
            $limit = BeforeModel::CommonGet('OrderItem',$modelData);
            if(count($limit['data'])>=$product['limit']){
                throw new ErrorMessage([
                    'msg' => '购买数量超限',
                ]);
            };
        };
        

        $modelData = [];
        $modelData['data']['order_no'] = $order_no;
        if(!$isSku){
            $modelData['data']['product_id'] = $product['id'];
        }else{
            $modelData['data']['sku_id'] = $product['id'];
        };
        
        $modelData['data']['title'] = $product['title'];
        $modelData['data']['price'] = $product['price'];
        $modelData['data']['count'] = $data['count'];
        $modelData['data']['snap_product'] = json_encode($product);
        $modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
        $modelData['data']['user_no'] = $user['user_no'];
        $modelData['FuncName'] = 'add';
        $orderItemRes = BeforeModel::CommonSave('OrderItem',$modelData);
        if(!$orderItemRes>0){
            throw new ErrorMessage([
                'msg' => '写入产品失败',
                'info'=>$product
            ]);  
        };
        return [
            'totalPrice'=>$product['price']*$data['count'],
            'count'=>$data['count'],
            'price'=>$product['price'],
            'id'=>$product['id'],
            'title'=>$product['title'],
        ];
        
    }



    public static function computePrice($data)
	{
        
        $price = (isset($data['balance'])?$data['balance']:0)+ (isset($data['score'])?floatval($data['score']):0) + (isset($data['wx_pay'])?$data['wx_pay']:0);
        if(isset($data['coupon'])){
            foreach ($data['coupon'] as $key => $value) {
                $modelData = [];
                $modelData['searchItem']['id'] = $value;
                $coupon = BeforeModel::CommonGet('Product',$modelData);
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