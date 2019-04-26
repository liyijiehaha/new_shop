<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');
    Route::resource('/goods',GoodsController::class);
    Route::resource('/order',OrderController::class);
    Route::resource('/WxUser',WxUserController::class);
    Route::resource('/Material',MaterialController::class);
});