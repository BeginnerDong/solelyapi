<?php
/**
 * Created by 董博明.
 * Author: 董博明
 * Date: 2018/4/9
 * Time: 19:23
 */

namespace app\api\controller\v1\weFunc;

use think\Controller;
use think\Db;
use think\Request as Request;
use think\Loader;

use app\api\controller\v1\weFunc\Message as WxMessage;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Project extends Controller{


	function __construct($data)
	{

	}

	
	/**
	 * searchItem条件搜索user+
	 * 参数包括
	 * @data_arr:信息内容
	 * @thirdapp_id:项目ID
	 */
	public static function sendMessage($data,$inner=false)
	{

		$modelData = [];
		if (isset($data['getBefore'])) {
			$modelData['getBefore'] = $data['getBefore'];
		};
		if (isset($data['searchItem'])) {
			$modelData['searchItem'] = $data['searchItem'];
		};
		$modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
		$modelData['searchItem']['status'] = 1;
		$user = BeforeModel::CommonGet('User',$modelData);

		$result = [];
		if(count($user['data'])>0){
			
			$user = $user['data'][0];
			/*发送信息由调起接口时传入*/
			$data_arr = $data['data_arr'];
			$post_data = array (
			  "touser"			 => $user['openid'],
			  // 小程序后台申请到的模板编号
			  "template_id"		 => $data['template_id'],
			  // 点击模板消息后跳转到的页面，可以传递参数,如"pages/index/index",
			  "page"			 => $data['page'],
			  // 跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
			  "miniprogram_state"=> "formal",
			  "lang"			 => 'zh_CN',
			  // 数据
			  "data"			 => $data_arr,
			);
			// 将数组编码为 JSON
			// $post_data = json_encode($post_data, true);   
			// 这里的返回值是一个 JSON，可通过 json_decode() 解码成数组
			$send = WxMessage::sendMessage($post_data,$data['thirdapp_id']);

			$result['code'] = $send['errcode'];
			$result['msg'] = $send['errmsg'];

			
		}else{

			// throw new SuccessMessage([
			// 	'msg'=>'用户不存在',
			// 	'info'=>$result,
			// ]);

		}

		if (!$inner) {
			
			if($send['errcode']==0){
				throw new SuccessMessage([
					'msg'=>'发送成功',
					'info'=>$result,
				]);
			}else{
				throw new ErrorMessage([
					'msg'=>'发送失败',
					'info'=>$result,
				]);
			};

		}else{

			return $result;

		}
		
	}

}