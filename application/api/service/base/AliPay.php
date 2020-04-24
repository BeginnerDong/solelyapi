<?php
/**
 * Created by 董博明.
 * Date: 2020/2/24
 * Time: 16:00
 */

namespace app\api\service\base;

use app\api\model\Log;
use app\api\model\ThirdApp;
use app\api\service\beforeModel\Common as BeforeModel;

use think\Exception;
use think\Loader;
use think\Cache;

use app\lib\exception\ErrorMessage;
use app\lib\exception\SuccessMessage;
use app\api\validate\CommonValidate as CommonValidate;

Loader::import('AliPay.AopClient', EXTEND_PATH, '.php');
Loader::import('AliPay.AlipayTradeAppPayRequest', EXTEND_PATH, '.php');


class AliPay
{

	function __construct()
	{

	}


	// 构建支付宝App支付订单信息
	public static function pay($userInfo,$pay_no,$price,$logData=[],$orderInfo)
	{
		$thirdInfo = ThirdApp::get(['id' => $userInfo['thirdapp_id']]);

		$aop = new \AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $thirdInfo['ali_appid'];
		$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAjBlNv02gIHl6T5VLcSLbHLyF7lH0EVGwUt1EIokmzcQoPqCOp8Ixj33W+3Yb6DJG0ZmZ5OBfK/LHF7AbpZ5093iJlsAWFyewc/cAjkQrBSZWkUsBRNusOg9Oee7CaFjaTjDaGt/qmOmR7chXrZDxyhYtj0b0WGhzgzVQrCXoymFkTxFwclqNBh5tu2A9VZbzVxVFfpunTxrYYQaKOHYnzyCypzR300AffF59q0I2cSBUTXFCksPEI2McsiTFq0o9uKvSMh0Lvc1oWHTVR3vaHwDTaMIOUiD5u3N/QF92NJSjrYm+LKXsgEMOPfQz1dnzso2TpruPMtGsMD81YQ/bHwIDAQABAoIBAB/0VYtgxTki/AbS4pY3gQqY5WNqReT9YN5FoZjO947PQu6l6GPTI0K7TSGl2s+nSAfepP9TPeHkSgzZGjU4Yy1ezOMZhPBbGSAaIoJQgg92oyAYn315LVtAyoF9N9JdEc3rzpN6pyOhgqtdmsH34W5aiKG5aVoQ2OgAbRoWu3YNUa4L2tVwhfj8LZO6QMPBrR049JYjtXBSY/0iOHs9/hv0fGFdJSqA/jkzz/J3BvEe6M4S3cJoaPb/amHqLy/X/6bZ8e8i8+EDBWi6gWBIq+RYbAkjH9GnXJvrSH7ZbdWcPzyn2rjBy17wUc8ZEYE2dPJNZWpPSNMTzWAuLwL+vkECgYEA1gNmzCrjnwIQVbplwUpAzHoja+jcqaNKKDAHLEG/Vi5eIHjKz2sOBtCAruIA4GMsiqkMwcK52fKSn5L/ctCBNq93n3JrN9UReirXq27yFnVDOSRx01cMjYnUM2iCcMa1j68DWN88+QJffi9i5FrWDvgLsXQZkrnk5Q8FXBTNDHcCgYEAp5Wf8KOdGLyRgmRzCx1yxYmbtZE4QB6hjVS4Bs0ogznShtiwGvOzoBEDH3HuFFtDA5RvzyjZ+P9qmXWoJdALR5YoeHeufOjSSi78R5juZNX9Kv1J+uqEC68OIP3Zpx2MhB68zHWLaBLA9I/9vgHU/+lkgAlQEfE1HEferwK52JkCgYAZtaTmyAw6MORHeDH7K3FnXxDcSMghdOVWuJZUAb37m2xhWEF8825m8StUVwAl9KQIMFDBAiSTgymME/uuDlBHgoLLW9J3jZgg5f6ssJJSklm2BqPJ8L4oTrN4TYjGBYkQLsUnKwJHI7rXDNhDeIoYmEHHWpwr3TsbLVfGfRU49wKBgEtpTj6oMYI3ILdvYkGHL+VqRfJPdeOMpSAHFoSg/3EFrRHXInAOaC9IWIJm7z6jzqUmv6WV+XhN33dM9ayGUP0WugFzwcYdsruFJytEy0n+7VzR+994dpEXZiE8ehv0dQ3jEwxifKgJgGDNBt7gziuWqA3hovFf/IlMYg2ZCW5JAoGBAJn+htToEM3gmFO8vYYVIRff8itnRHER7X65dyzwo24QJdyqr00aUfia+uc3EWn3QLQTYwIReWqN4Tht73CFZM+KMscdUFld8IGxtkyadb2XSk/X0aF8QLd7O8WLBV1SL33X6jnDVr/RLo0HsxKF7CJiBoN9lZLLFW76UVxZ88fq';
		$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoWSLrVf6gIW3krFJuIE6gx9Jfjjd/Bs1bN0XuhXq86cx1p1RaQkiweTD/naiQKctm+Du3Ar5rtkAy0SzUzbTKMSJ/9ijXgRP8hs304MKUyU9enyNYi4JNonPXNvWUB2fkqF1MR1IVa1Ih7NuLilz09SkwCw+xrvVqpF8KjHN+FF+kzDrneVI45sJji1bLOBKMnemirZFNFyBzxeGprw0/KGeGBmDJt6tX55B4fUu7i+yU+l0FShYWG73CBerpy6jaxSQz0b0Rk6jGsUw4NyS1yhm/9aj4pK3/UMyf0AzdpydfvU2yJhwzenrFy9IrOlspwlvwqE2/oDzu2hZpNO2cwIDAQAB';
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'utf-8';
		$aop->format = 'json';

		$request = new \AlipayTradeAppPayRequest();
		$request->setBizContent(
			"{" .
			"\"subject\":\"".$orderInfo['title']."\"," .
			"\"out_trade_no\":\"".$pay_no."\"," .
			"\"total_amount\":\"".$price."\"," .
			"}"
			);
		$request->setNotifyUrl(config('secure.alipay_back_url'));
		
		if($pay_no){
			$modelData = [];
			$modelData['data'] = array_merge(array(
				'title'=>'支付宝支付',
				'pay_no'=>$pay_no,
				'price'=>$price,
				'order_no'=>isset($orderInfo['order_no'])?$orderInfo['order_no']:'',
				'parent_no'=>isset($orderInfo['parent_no'])?$orderInfo['parent_no']:'',
				'create_time'=>time(),
				'type'=>2,
				'user_no'=>$userInfo['user_no'],
				'thirdapp_id'=>$thirdInfo['id'],
			),$logData);
			$modelData['FuncName'] = 'add';
			$saveLog = BeforeModel::CommonSave('PayLog',$modelData);
		};
		
		//这里和普通的接口调用不同，使用的是sdkExecute
		$result = $aop->sdkExecute($request);
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
		//echo htmlspecialchars($result);//就是orderString 可以直接给客户端请求，无需再做处理。
		throw new SuccessMessage([
			'msg'=>'支付宝支付发起成功',
			'info'=>$result,
		]);

	}


	//验证订单支付
	public static function verify($data)
	{
		$aop = new \AopClient();
		$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoWSLrVf6gIW3krFJuIE6gx9Jfjjd/Bs1bN0XuhXq86cx1p1RaQkiweTD/naiQKctm+Du3Ar5rtkAy0SzUzbTKMSJ/9ijXgRP8hs304MKUyU9enyNYi4JNonPXNvWUB2fkqF1MR1IVa1Ih7NuLilz09SkwCw+xrvVqpF8KjHN+FF+kzDrneVI45sJji1bLOBKMnemirZFNFyBzxeGprw0/KGeGBmDJt6tX55B4fUu7i+yU+l0FShYWG73CBerpy6jaxSQz0b0Rk6jGsUw4NyS1yhm/9aj4pK3/UMyf0AzdpydfvU2yJhwzenrFy9IrOlspwlvwqE2/oDzu2hZpNO2cwIDAQAB';
		$flag = $aop->rsaCheckV1($data, NULL, "RSA2");
		return $flag;
	}


    //关闭支付宝订单
    public static function closeorder($orderinfo)
    {
		return;
    }


    //订单退款
    public static function refundOrder($flowLogID)
	{
		return;
    }

}