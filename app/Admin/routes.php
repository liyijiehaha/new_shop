<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');
    Route::resource('/Goods',GoodsController::class);
    Route::resource('/Order',OrderController::class);
    Route::resource('/WxUser',WxUserController::class);
    //素材
    Route::resource('/Material',MaterialController::class);
    //群发
    Route::resource('/Message',MessageController::class);
    Route::get('/MessageAdd','MessageController@Add');
    Route::get('/Getaccesstoken','MessageController@getaccesstoken');
    //素材添加页面
    Route::get('Fodder','FodderController@index');
    Route::post('fodderAdd','FodderController@fodderAdd');
    Route::get('/getaccesstoken','FodderController@getaccesstoken');
    //文件上传素材管理
    Route::get('UploadMateril','UploadMaterailController@index');
});
