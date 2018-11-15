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

//测试跨域
Route::post('api/:version/ThirdApp/t','api/:version.ThirdApp/test');
//测试接口
Route::post('api/:version/solelytest','api/:version.Test/test');
//测试微信支付回调函数
Route::post('api/:version/return','api/:version.Test/return');