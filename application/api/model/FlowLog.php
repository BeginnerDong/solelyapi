<?php


namespace app\api\model;


use think\Model;

use app\api\model\User;

use app\api\model\UserInfo;
use app\api\model\Order;

use app\lib\exception\ErrorMessage;

use app\api\service\base\WxPay;

class FlowLog extends Model
{

	public static function dealAdd($data)
	{

		$standard = [
			'type'=>'',
			'count'=>'',
			'relation_id'=>'',
			'standard_id'=>'',
			'create_time'=>time(),
			'update_time'=>'',
			'delete_time'=>'',
			'user_no'=>'',
			'user_type'=>'',
			'status'=>1,
			'trade_info'=>'',
			'relation_table'=>'',
			'relation_user'=>'',
			'thirdapp_id'=>'',
			'order_no'=>'',
			'pay_no'=>'',
			'product_no'=>'',
			'behavior'=>1,
			'payInfo'=>'',
			'extra_info'=>'',
			'parent_no'=>'',
			'level'=>'',
			'account'=>1,
			'withdraw'=>0,
			'withdraw_status'=>0,
		];

		if(isset($data['data']['user_no'])){

			$res = User::get(['user_no' => $data['data']['user_no']]);

			$UserInfo = UserInfo::get(['user_no' => $data['data']['user_no']]);

			if($res){

				$data['data']['user_type'] = $res['user_type'];

			}else{

				throw new ErrorMessage([

					'msg' => '关联user信息有误',

				]);

			};

		};

		$data['data'] = chargeBlank($standard,$data['data']);

		if(isset($data['data']['type'])&&($data['data']['type']==2||$data['data']['type']==3)&&isset($data['data']['status'])&&($data['data']['status']==1)){

			if($data['data']['type']==2){

				$where['type'] = 2;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $data['data']['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num + $data['data']['count'];
				
				$res = UserInfo::where('user_no', $data['data']['user_no'])->update(['balance' => $num]);

			}else if($data['data']['type']==3){

				$where['type'] = 3;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $data['data']['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num + $data['data']['count'];

				$res = UserInfo::where('user_no', $data['data']['user_no'])->update(['score' => $num]);

			};

		};

		return $data;

	}



	public static function dealGet($data)
	{

		return $data;

	}



	public static function dealUpdate($data)
	{

		
		if(isset($data['data']['type'])||isset($data['data']['count'])){
			throw new ErrorMessage([
				'msg' => '不允许编辑的字段',
			]);
		};

		$FlowLogInfo = FlowLog::get($data['searchItem']);

		$UserInfo = UserInfo::get([
			'status'=>1,
			'user_no'=>$FlowLogInfo['user_no']
		]);

		//流水执行
		if (isset($data['data']['status'])&&$data['data']['status']==1&&$FlowLogInfo['status']!=1) {

			if($FlowLogInfo['type']==2){
			
				$where['type'] = 2;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $UserInfo['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num + $FlowLogInfo['count'];
				
				$res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['balance' => $num]);
				
			}else if($FlowLogInfo['type']==3){
			
				$where['type'] = 3;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $UserInfo['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num + $FlowLogInfo['count'];
				
				$res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['score' => $num]);
				
			};

		}

		//流水退还
		if(isset($data['data']['status'])&&$data['data']['status']==-1&&$FlowLogInfo['status']==1){ 

			if($FlowLogInfo['type']==2){
			
				$where['type'] = 2;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $UserInfo['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num - $FlowLogInfo['count'];
				
				$res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['balance' => $num]);
				
			}else if($FlowLogInfo['type']==3){

				$where['type'] = 3;
				$where['status'] = 1;
				$where['account'] = 1;
				$where['user_no'] = $UserInfo['user_no'];
				$num = FlowLog::where($where)->sum('count');
				$num = $num - $FlowLogInfo['count'];
				
				$res = UserInfo::where('user_no', $UserInfo['user_no'])->update(['score' => $num]);
				
			}else if($FlowLogInfo['type']==1){

				WxPay::refundOrder($FlowLogInfo['id']);

			};

		};

		return $data;

	}


	public static function dealRealDelete($data)
	{

		return $data;

	}
}