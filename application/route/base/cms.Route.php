<?php

use think\Route;

Route::post('api/:version/token/app', 'api/:version.base.Token/getAppToken');


//image
Route::post('api/:version/Base/:serviceName/:FuncName', 'api/:version.base.Main/Base');

Route::post('api/:version/Func/:serviceName/:FuncName', 'api/:version.base.Main/Func');


Route::any('api/:version/Common/:modelName/:FuncName', 'api/:version.base.Main/Common');

Route::post('api/:version/Project/:serviceName/:FuncName', 'api/:version.base.Main/Project');




//Route::post('api/:version/Admin/:serviceName', 'api/:version.base.Admin/admin');

//image
//Route::post('api/:version/:serviceName/:serviceFuncName', 'api/:version.base.Image/Main');




//修改后台用户信息
/*Route::post('api/:version/Admin/Update', 'api/:version.base.Admin/UpdateAdmin');
//软删除后台用户信息
Route::post('api/:version/Admin/Del', 'api/:version.base.Admin/DelAdmin');
//获取后台用户列表
Route::post('api/:version/Admin/List', 'api/:version.base.Admin/getAllAdmin');
//后台用户登录
Route::post('api/:version/Admin/Login', 'api/:version.base.Admin/LoginByAdmin');
//获取指定admin用户信息
Route::post('api/:version/Admin/GetInfo', 'api/:version.base.Admin/getAdminInfo');
//admin用户登出
Route::post('api/:version/Admin/out', 'api/:version.base.Admin/loginOut');
//admin用户修改密码
Route::post('api/:version/Admin/uptPass', 'api/:version.base.Admin/updatePassWord');
//获取我的用户信息
Route::post('api/:version/Admin/GetMyInfo', 'api/:version.base.Admin/getMyInfo');
//修改我的用户信息
Route::post('api/:version/Admin/UpdateMyInfo', 'api/:version.base.Admin/updateMyInfo');*/