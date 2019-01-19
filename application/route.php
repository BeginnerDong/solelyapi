<?php



/**

 * 路由注册

 *

 * 以下代码为了尽量简单，没有使用路由分组

 * 实际上，使用路由分组可以简化定义

 * 并在一定程度上提高路由匹配的效率

 */

// 写完代码后对着路由表看，能否不看注释就知道这个接口的意义

use think\Route;

require_once (dirname(__FILE__).'/route/test.Route.php');

require_once (dirname(__FILE__).'/route/base/cms.Route.php');





Route::post('api/:version/pay/notify','api/:version.WXPayReturn/receiveNotify');






//计时器接口

/*分钟计时器，暂定每5分钟执行一次*/

Route::get('api/:version/Timer/TimerByMins','api/:version.Timer/timerByMins');

Route::get('api/:version/Timer/TimerByMins','api/:version.Timer/timerByMins');




//微信公众号接口

/*公众号接口入口*/

Route::any('api/:version/WxController/Index/:thirdapp_id','api/:version.WxController/index');



/*公众号网页授权*/

Route::any('api/:version/WxJssdk','api/:version.WxJssdk/getSignPackage');

Route::any('api/:version/WxAuth','api/:version.WxAuth/index');




//Token

Route::post('api/:version/token/user', 'api/:version.Token/getToken');



Route::post('api/:version/token/verify', 'api/:version.Token/verifyToken');

//不想把所有查询都写在一起，所以增加by_user，很好的REST与RESTFul的区别

//Route::get('api/:version/order/by_user', 'api/:version.Order/getSummaryByUser');

//Route::get('api/:version/order/paginate', 'api/:version.Order/getSummary');


/*回调函数*/ 

Route::post('api/:version/pay/refund','api/:version.MerchantPay/refund');