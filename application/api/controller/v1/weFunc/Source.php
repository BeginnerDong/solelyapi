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

use app\api\controller\v1\weFunc\Base as WxBase;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;	


class Source extends Controller{


	function __construct($data)
	{

	}

	/**
	 * 需要信息：
	 * paginate.currentPage
	 * type:图片image;视频video;语音voice;图文news
	 */
	public static function getSource($data,$inner=false)
	{

		$currentPage = $data['paginate']['currentPage'];

		if (isset($data['thirdapp_id'])) {
			
			$modelData = [];
	        $modelData['searchItem'] = [
	            'id'=>$data['thirdapp_id']
	        ];
	        $thirdInfo = BeforeModel::CommonGet('ThirdApp',$modelData);

			if(count($thirdInfo['data'])>0){

				$thirdInfo = $thirdInfo['data'][0];

				if($thirdInfo['access_token']&&$thirdInfo['access_token_expire']>time()){

					$access_token = $thirdInfo['access_token'];

				}else{

					$info['thirdapp_id'] = $thirdInfo['id'];
					$info['appid'] = $thirdInfo['wx_appid'];
					$info['appsecret'] = $thirdInfo['wx_appsecret'];
					$access_token = WxBase::getAccessToken($info);

				}; 

			}else{

				throw new ErrorMessage([
		            'msg'=>'关联项目不存在',
		        ]);

			};

		}else if(isset($data['wechat_id'])){

			$modelData = [];
	        $modelData['searchItem'] = [
	            'id'=>$data['wechat_id']
	        ];
	        $wechatInfo = BeforeModel::CommonGet('Wechat',$modelData);

	        if(count($wechatInfo['data'])>0){

				$wechatInfo = $wechatInfo['data'][0];

				if($wechatInfo['access_token']&&$wechatInfo['access_token_expire']>time()){

					$access_token = $wechatInfo['access_token'];

				}else{

					$access_token = WxBase::getAccessToken($wechatInfo,true);

					//保存access_token
					$modelData = [];
					$modelData['searchItem'] = [
			            'id'=>$data['wechat_id'],
			        ];
			        $modelData['data'] = array(
			            'access_token'=>$access_token,
			            'access_token_expire'=>time()+7000,
			        );
			        $modelData['FuncName'] = 'update';
			        $upThird = BeforeModel::CommonSave('Wechat',$modelData);

				}; 

			}else{

				throw new ErrorMessage([
		            'msg'=>'关联项目不存在',
		        ]);

			};
		}


		$post_data = array(
		    "type"=>$data['type'],
		    "offset"=>$currentPage-1,
		    "count"=>1,
		);

		$url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=".$access_token;

		$source = WxBase::curl_wxpost($url,$post_data);

		$source = json_decode($source,true);
		
		$res['total'] = $source['total_count'];
		$res['data'] = [];
		if ($source['item_count']>0) {

			foreach ($source['item'] as $key => $value) {

				$media_id = $value['media_id'];

				foreach ($value['content']['news_item'] as $c_key => $c_value) {

					$info['title'] = $c_value['title'];
					$info['content'] = $c_value['content'];
					$info['mainImg'] = array(['name'=>'主图','url'=>$c_value['thumb_url']]);
					$info['media_id'] = $media_id;
					$info['update_time'] = $value['update_time'];
					$info['url'] = $c_value['url'];

					array_push($res['data'],$info);
				}
			}
		}

		$res['data'] = BeforeModel::CommonGetAfter($data,$res['data']);

		if ($source['item_count']>0) {
			throw new SuccessMessage([
	            'msg'=>'查询成功',
	            'info'=>$res
	        ]);
		}else{
			throw new SuccessMessage([
                'msg'=>'查询结果为空',
                'info'=>$res
            ]);
		}
	    
	}
}