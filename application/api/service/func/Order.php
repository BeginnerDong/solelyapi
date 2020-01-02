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



	/*生成组合单*/
	public static function addMultiOrder($data)
	{

		(new CommonValidate())->goCheck('one',$data);

		//生成父订单
		$parent_no = makeOrderNo();
		$modelData = [];
		$data['order_no'] = $parent_no;
		$modelData = self::createVirtualOrderData($data);
		$modelData['FuncName'] = 'add';
		$parentOrder = BeforeModel::CommonSave('Order',$modelData);

		$totalPrice = 0;
		$childOrder = [];

		$modelData = [];
		foreach ($data['orderList'] as $key => $value) {
			$value['token'] = $data['token'];
			$value['data']['parent_no'] = $parent_no;
			$modelData = self::createOrderData($value);
			$totalPrice += $modelData['data']['price'];
			$modelData['FuncName'] = 'add';
			$oneOrder = BeforeModel::CommonSave('Order',$modelData);
			array_push($childOrder,$oneOrder);
		}

		$modelData = [];
		$modelData['FuncName'] = 'update';
		$modelData['searchItem'] = [
			'id'=>$parentOrder
		];
		$modelData['data']['price'] = $totalPrice;
		$updateParentOrder = BeforeModel::CommonSave('Order',$modelData);

		if($parentOrder>0){
			if(isset($data['pay'])){
				$pay = $data['pay'];
				$pay['searchItem'] = ['id'=>$parentOrder];
				return PayService::pay($pay,true);
			}else{
				throw new SuccessMessage([
					'msg'=>'下单成功',
					'info'=>[
						'id'=>$parentOrder,
						'childOrder'=>$childOrder
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
		
		if (!isset($data['orderList'])) {
			throw new ErrorMessage([
				'msg' => '缺少订单参数orderList',
			]);
		};
		
		$count = count($data['orderList']);

		if ($count>1) {
		
			return self::addMultiOrder($data);
		
		}else if($count==1){
			/*生成父级订单*/
			if(isset($data['parent'])){
				$parent_no = makeOrderNo();
				$data['order_no'] = $parent_no;
				$modelData = [];
				$modelData = self::createVirtualOrderData($data);
				$modelData['FuncName'] = 'add';
				$parentOrder = BeforeModel::CommonSave('Order',$modelData);
				$data['orderList'][0]['parent_no'] = $parent_no;
				$info['parent_id'] = $parentOrder;
			};
			
			$data['orderList'][0]['token'] = $data['token'];
			$modelData = [];
			$modelData = self::createOrderData($data['orderList'][0]);
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
		};

	}



	/**
	 * 生成订单数据
	 * @param product_id/sku_id购买商品ID
	 * @param count购买商品数量
	 * @param day_time购买指定日期库存，当日时间戳0点
	 * @param isGroup团购单传递，值为true
	 * @param group_no团购单传递，团购单号
	 */
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

		if(!isset($data['product_id'])&&!isset($data['sku_id'])) {
			throw new ErrorMessage([
				'msg' => '购买商品信息错误',
			]);
		};
		if(!isset($data['count'])) {
			throw new ErrorMessage([
				'msg' => '未选择购买数量',
			]);
		};
		
		if(isset($data['isGroup'])){
			$isGroup = true;
		}else{
			$isGroup = false;
		};
		
		$checkInfo = self::checkStock($data,$order_no,$user,$type,$isGroup);

		$modelData = [];
		$modelData['data']['order_no'] = $order_no;
		$modelData['data']['price'] = $checkInfo['totalPrice'];
		$modelData['data']['title'] = $checkInfo['productInfo']['title'];
		$modelData['data']['unit_price'] = $checkInfo['productInfo']['price'];
		$modelData['data']['count'] = $data['count'];
		$modelData['data']['type'] = $type;
		$modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
		$modelData['data']['user_no'] = $user['user_no'];
		$modelData['data']['product_id'] = isset($data['product_id'])?$data['product_id']:0;
		$modelData['data']['sku_id'] = isset($data['sku_id'])?$data['sku_id']:0;
		$modelData['data']['snap_address'] = isset($data['snap_address'])?$data['snap_address']:'';
		$modelData['data']['products'] = isset($data['products'])?$data['products']:'';

		if(isset($data['data'])){
			$modelData['data'] = array_merge($data['data'],$modelData['data']);
		};

		//判断是否是团购商品
		if(isset($data['isGroup'])&&!isset($data['group_no'])){
			$modelData['data']['group_no'] = makeGroupNo();
			$modelData['data']['group_leader'] = "true";
			$modelData['data']['group_status'] = 1;
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
				$modelData['data']['group_status'] = 1;
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
		$modelData['data']['type'] = 6;
		$modelData['data']['pay'] = isset($data['pay'])?json_encode($data['pay']):json_encode([]);
		$modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
		$modelData['data']['user_no'] = $user['user_no'];
		return $modelData;
	}



	/**
	 * @param data下单的信息
	 * @param order_no订单NO
	 * @param user下单人信息
	 * @param type订单类型
	 * @return 计算出的金额
	 */
	public static function checkStock($data,$order_no,$user,$type,$isGroup)
	{
		if(isset($data['product_id'])){
			$product = true;
		}else if(isset($data['sku_id'])){
			$product = false;
		}
		
		$modelData = [];
		if($product){
			$modelData['searchItem']['id'] = $data['product_id'];
			$product = BeforeModel::CommonGet('Product',$modelData);
		}else{
			$modelData['searchItem']['id'] = $data['sku_id'];
			$product = BeforeModel::CommonGet('Sku',$modelData);
		};
		if(!count($product['data'])>0){
			throw new ErrorMessage([
				'msg' => '产品不存在或已下架',
				'info'=>$product
			]); 
		};
		$product = $product['data'][0];

		/*检测库存*/
		if($type<4){
			$modelData = [];
			$modelData['getOne'] = 'true';
			if($product){
				$modelData['searchItem']['product_no'] = $product['product_no'];
			}else{
				$modelData['searchItem']['sku_no'] = $product['sku_no'];
			};
			if(isset($data['day_time'])){
				$modelData['searchItem']['day_time'] = $data['day_time'];
				$modelData['searchItem']['type'] = 2;
			}else{
				$modelData['searchItem']['type'] = 1;
			};
			$stock = BeforeModel::CommonGet('ProductDate',$modelData);
			if(count($stock['data'])>0){
				$stock = $stock['data'][0];
				if(($isGroup&&($stock['group_stock']<$data['count']))||($stock['stock']<$data['count'])){
					throw new ErrorMessage([
						'msg' => '库存不足',
						'info'=>$product
					]);
				};
			}else{
				throw new ErrorMessage([
					'msg' => '库存异常',
					'info'=>$product
				]);
			};
		};

		if($product['limit']>0){
			$modelData = [
				'searchItem'=>[]
			];
			if($product){
				$modelData['searchItem']['product_id'] = $data['product_id'];
			}else{
				$modelData['searchItem']['sku_id'] = $data['sku_id'];
			};
			$modelData['searchItem']['user_no'] = $user['user_no'];
			$modelData['searchItem']['pay_status'] = 1;
			$limit = BeforeModel::CommonGet('Order',$modelData);
			if(count($limit['data'])>=$product['limit']){
				throw new ErrorMessage([
					'msg' => '购买数量超限',
				]);
			};
		};

		/*套餐商品*/
		if(isset($data['data']['set_no'])){
			$modelData = [];
			$modelData['getOne'] = 'true';
			$modelData['searchItem']['relation_one'] = $data['data']['set_no'];
			$modelData['searchItem']['relation_two'] = $product['product_no'];
			$relation = BeforeModel::CommonGet('Relation',$modelData);
			if(count($relation['data'])>0){
				$relation = $relation['data'][0];
				$product['price'] = $relation['price'];
			}else{
				throw new ErrorMessage([
					'msg' => '套餐不存在',
				]);
			}
		};

		$modelData = [];
		$modelData['data']['order_no'] = $order_no;
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
		$totalPrice = $product['price']*$data['count'];
		return [
			'totalPrice'=>$totalPrice,
			'productInfo'=>$product,
		];
	}

}