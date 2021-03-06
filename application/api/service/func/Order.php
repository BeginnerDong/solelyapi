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

	

	/**
	 * 订单生成组合单
	 * 母单记录订单相关数据，如支付状态、运输状态、团购信息
	 * 子单记录购买的商品信息、数量
	 */
	public static function addOrder($data)
	{

		(new CommonValidate())->goCheck('one',$data);
		$info = [];
		
		if (!isset($data['orderList'])) {
			throw new ErrorMessage([
				'msg' => '缺少订单参数orderList',
			]);
		};
		//子单数量
		$count = count($data['orderList']);

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
			$value['parent_no'] = $parent_no;
			if(isset($data['data']['group_status'])){
				$value['group_status'] = $data['data']['group_status'];
			};
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



	/**
	 * 生成订单数据
	 * @param product_id/sku_id购买商品ID
	 * @param count购买商品数量
	 * @param day_time购买指定日期库存，当日时间戳0点
	 */
	public static function createOrderData($data)
	{
		
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
		$level = isset($data['data']['level'])?$data['data']['level']:0;
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
		
		if(isset($data['data']['group_status'])){
			$isGroup = true;
		}else{
			$isGroup = false;
		};

		$checkInfo = self::checkStock($data,$order_no,$user,$level,$isGroup);

		$modelData = [];
		$modelData['data']['order_no'] = $order_no;
		$modelData['data']['parent_no'] = $data['parent_no'];
		$modelData['data']['price'] = $checkInfo['totalPrice'];
		$modelData['data']['product_title'] = $checkInfo['productInfo']['product_title'];
		$modelData['data']['sku_title'] = $checkInfo['productInfo']['sku_title'];
		$modelData['data']['unit_price'] = $checkInfo['productInfo']['price'];
		$modelData['data']['count'] = $data['count'];
		$modelData['data']['level'] = $level;
		$modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
		$modelData['data']['user_no'] = $user['user_no'];
		$modelData['data']['product_id'] = isset($data['product_id'])?$data['product_id']:0;
		$modelData['data']['sku_id'] = isset($data['sku_id'])?$data['sku_id']:0;

		// if(isset($data['data'])){
		// 	$modelData['data'] = array_merge($data['data'],$modelData['data']);
		// };

		return $modelData;
	}



	/**
	 * 生成父级订单数据
	 * @param group_status团购单传递，值为1
	 * @param group_no团购单传递，团购单号
	 */
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
		$modelData['data']['level'] = 1;
		$modelData['data']['type'] = isset($data['data']['type'])?$data['data']['type']:6;
		$modelData['data']['pay'] = isset($data['pay'])?json_encode($data['pay']):json_encode([]);
		$modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
		$modelData['data']['user_no'] = $user['user_no'];
		
		//因订单渲染逻辑，团购信息需要记录到父级订单上
		//判断是否是团购商品
		if(isset($data['data']['group_status'])&&$data['data']['group_status']==1&&!isset($data['data']['group_no'])){
			$modelData['data']['group_no'] = makeGroupNo();
			$modelData['data']['group_leader'] = "true";
			$modelData['data']['standard'] = isset($data['data']['standard'])?$data['data']['standard']:'';
		}else if(isset($data['data']['group_status'])&&$data['data']['group_status']==1&&isset($data['data']['group_no'])){
			$c_modelData = [];
			$c_modelData['searchItem'] = [
				'group_no'=>$data['data']['group_no']
			];
			$groupRes = BeforeModel::CommonGet('Order',$c_modelData);
			if(count($groupRes['data'])>0){
				$modelData['data']['group_no'] = $data['data']['group_no'];
				$modelData['data']['standard'] = $groupRes['data'][0]['standard'];
				$modelData['data']['group_status'] = 1;
			}else{
				throw new ErrorMessage([
					'msg' => 'group_no不存在',
				]);
			};
		};
		
		return $modelData;
	}



	/**
	 * @param data下单的信息
	 * @param order_no订单NO
	 * @param user下单人信息
	 * @param level订单层级，0为子单，检测库存
	 * @return 计算出的金额
	 */
	public static function checkStock($data,$order_no,$user,$level,$isGroup)
	{
		if(isset($data['product_id'])){
			$product = true;
		}else if(isset($data['sku_id'])){
			$product = false;
		}
		
		$modelData = [];
		if($product){
			$modelData['searchItem']['id'] = $data['product_id'];
			$info = BeforeModel::CommonGet('Product',$modelData);
		}else{
			$modelData['searchItem']['id'] = $data['sku_id'];
			$info = BeforeModel::CommonGet('Sku',$modelData);
		};
		if(count($info['data'])==0){
			throw new ErrorMessage([
				'msg' => '产品不存在或已下架',
				'info'=>$info
			]); 
		};
		$info = $info['data'][0];
		if(!$product){
			$info['product_title'] = $info['product']['title'];
			$info['sku_title'] = $info['title'];
		}else{
			$info['product_title'] = $info['title'];
			$info['sku_title'] = '';
		};

		/*检测库存*/
		if($level==0){
			$modelData = [];
			$modelData['getOne'] = 'true';
			if($product){
				$modelData['searchItem']['product_no'] = $info['product_no'];
			}else{
				$modelData['searchItem']['sku_no'] = $info['sku_no'];
			};
			//默认判断当天的库存
			$beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
			if($info['is_date']==1){
				$modelData['searchItem']['day_time'] = $beginToday;
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
						'info'=>$info
					]);
				};
			}else{
				throw new ErrorMessage([
					'msg' => '库存异常',
					'info'=>$info
				]);
			};
		};

		if($info['limit']>0){
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
			if(count($limit['data'])>=$info['limit']){
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
			$modelData['searchItem']['relation_two'] = $info['product_no'];
			$relation = BeforeModel::CommonGet('Relation',$modelData);
			if(count($relation['data'])>0){
				$relation = $relation['data'][0];
				$info['price'] = $relation['price'];
			}else{
				throw new ErrorMessage([
					'msg' => '套餐不存在',
				]);
			}
		};

		$modelData = [];
		$modelData['data']['order_no'] = $order_no;
		$modelData['data']['snap_product'] = json_encode($info);
		$modelData['data']['thirdapp_id'] = $user['thirdapp_id'];
		$modelData['data']['user_no'] = $user['user_no'];
		$modelData['FuncName'] = 'add';
		$orderItemRes = BeforeModel::CommonSave('OrderItem',$modelData);
		if(!$orderItemRes>0){
			throw new ErrorMessage([
				'msg' => '写入产品失败',
				'info'=>$info
			]);  
		};
		$totalPrice = $info['price']*$data['count'];
		return [
			'totalPrice'=>$totalPrice,
			'productInfo'=>$info,
		];
	}

}