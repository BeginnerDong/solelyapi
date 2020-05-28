<?php

/**

 * Created by 董博明.

 * User: 董博明

 * Date: 2018/4/10

 * Time: 18:25

 */

namespace app\api\service\base;

use think\Cache;

use think\Loader;

use think\Request as Request; 

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\service\base\QiniuImage as QiniuImageService;

use app\api\service\base\FtpFile as FtpImageService;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


Loader::import('phpqrcode.phpqrcode', EXTEND_PATH, '.php');


/**

 * 用户获取场景二维码

 */

class Qr{


	//data需要appid,appsecret,qrInfo,output,thirdapp_id,user_no
	public static function ProgramQrGet($data,$inner=false)
	{
		
		if(!$inner){
		
			$data = Request::instance()->param();
			
			(new CommonValidate())->goCheck('one',$data);

			checkTokenAndScope($data,config('scope.two'));
		
			$userinfo = Cache::get($data['token']);
		
			$thirdapp_id = $userinfo['thirdapp_id'];
		
			$user_no = $userinfo['user_no'];
		
		}else{
		
			$thirdapp_id = $data['thirdapp_id'];
		
			$user_no = $data['user_no'];
		
		};

		$modelData = [];

		$modelData['searchItem']['id'] = $thirdapp_id;

		$ThirdAppInfo=BeforeModel::CommonGet('ThirdApp',$modelData);

		$ThirdAppInfo = $ThirdAppInfo['data'][0];

		$appid = $ThirdAppInfo['appid'];

		$appsecret = $ThirdAppInfo['appsecret'];

		$modelData = [];

		$modelData['searchItem'] = [

			'type'=>1,

			'user_no'=>$user_no,

			'param'=>json_encode($data['qrInfo']),

			'behavior'=>2

		];

		$res = BeforeModel::CommonGet('File',$modelData);
		
		if(count($res['data'])>0){
			
			if(!$inner){
				
				throw new SuccessMessage([
				
					'msg'=>'获取二维码图片成功',
				
					'info'=>['url'=>$res['data'][0]['path']]
				
				]);
				
			}else{
				
				return $res['data'][0]['path'];
				
			}

		};

		$tokenurl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;

		$result = curl_get($tokenurl);

		if ($result) {

			$result = json_decode($result,true);

			$access_token = $result['access_token'];

			$codeurl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;

			$stream = curl_post($codeurl,$data['qrInfo']);

			if($data['output']=='url'){

				$modelData = [];

				$modelData['stream'] = $stream;
				
				$modelData['md5'] = md5($stream);
				
				$modelData['chunkSize'] = 2* 1024 * 1024;
				
				$modelData['totalSize'] = strlen($stream);
				
				$modelData['start'] = 0;
				
				$modelData['originName'] = '小程序二维码';
				
				$modelData['thirdapp_id'] = $thirdapp_id;

				$modelData['user_no'] = $user_no;

				$modelData['behavior'] = 2;

				$modelData['type'] = 1;

				$modelData['ext'] = $data['ext'];

				$modelData['param'] = json_encode($data['qrInfo']);

				$url = FtpImageService::upload($modelData,true);
				
				if(!$inner){
					
					throw new SuccessMessage([
					
						'msg'=>'获取二维码图片成功',
					
						'info'=>['url'=>$url]
					
					]);
					
				}else{
					
					return $url;
					
				};

			}else{

				$result=self::data_uri($stream,'image/png');

				return $result;

			};

		}

	}



	/**
	 * [PHPQrGet 使用phpqrcode本地生成二维码]
	 * @param [type]  $data  [token/params/ext目前支持png]
	 * @param boolean $inner [description]
	 */
	public static function PHPQrGet($data,$inner=false)
	{

		if(!$inner){
			
			(new CommonValidate())->goCheck('one',$data);
			
			checkTokenAndScope($data,config('scope.two'));
			
			$thirdapp_id = Cache::get($data['token'])['thirdapp_id'];
			
			$user_no = Cache::get($data['token'])['user_no'];
			
		}else{
			
			$thirdapp_id = $data['thirdapp_id'];
			
			$user_no = $data['user_no'];
			
		}
		
		/*判断图片是否已生成*/
		$modelData = [];
		
		$modelData['searchItem'] = [
		
			'user_no'=>$user_no,
		
			'param'=>$data['param'],
		
		];
		
		$res = BeforeModel::CommonGet('File',$modelData);
		
		if(count($res['data'])>0){
		
			if(!$inner){
				
				throw new SuccessMessage([
						
					'msg'=>'获取二维码图片成功',
						
					'info'=>['url'=>$res['data'][0]['path']]
						
				]);
				
			}else{
				
				return $res['data'][0]['path'];
				
			}
		
		};

		$value = $data['param'];        //二维码内容

		$errorCorrectionLevel = 'L';    //容错级别

		$matrixPointSize = 5;           //生成图片大小

		$ext = $data['ext'];

		$qrCode = new \QRcode();

		ob_start();

		$qrCode::png($value);

		$stream = ob_get_contents();

		ob_end_clean();

		$modelData = [];

		$modelData['stream'] = $stream;
		
		$modelData['md5'] = md5($stream);
		
		$modelData['chunkSize'] = 2* 1024 * 1024;
		
		$modelData['totalSize'] = strlen($stream);
		
		$modelData['start'] = 0;
		
		$modelData['originName'] = '核销二维码';
		
		$modelData['thirdapp_id'] = $thirdapp_id;

		$modelData['user_no'] = $user_no;

		$modelData['behavior'] = 2;

		$modelData['type'] = 1;

		$modelData['param'] = $data['param'];

		$modelData['ext'] = $data['ext'];

		$url = FtpImageService::upload($modelData,true);

		if(!$inner){

			throw new SuccessMessage([

				'msg'=>'二维码生成成功',

				'info'=>['url'=>$url]

			]);

		}else{

			return $url;

		};

	}


	public static function data_uri($contents, $mime)
	{

		$base64   = base64_encode($contents);

		return ('data:' . $mime . ';base64,' . $base64);

	}

}