<?php



use think\Route;



Route::post('api/:version/token/app', 'api/:version.base.Token/getAppToken');




Route::any('api/:version/Base/:serviceName/:FuncName', 'api/:version.base.Main/Base');



Route::post('api/:version/Func/:serviceName/:FuncName', 'api/:version.base.Main/Func');


Route::post('api/:version/WeFunc/:WeFuncName/:FuncName', 'api/:version.base.Main/WeFunc');


Route::any('api/:version/Common/:modelName/:FuncName', 'api/:version.base.Main/Common');



Route::post('api/:version/Project/:serviceName/:FuncName', 'api/:version.base.Main/Project');