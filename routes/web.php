<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/info', function () {
    phpinfo();
});
//Auth
Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');
//微信接口返回文件
Route::get('/weixin/list','Weixin\WxController@list');
Route::post('/weixin/list','Weixin\WxController@wxEvent');
/*获取access_token*/
Route::get('/weixin/getaccesstoken','Weixin\WxController@getaccesstoken');
/*菜单*/
Route::get('/weixin/create_menu','Weixin\WxController@create_menu');
/*群发*/
Route::get('weixin/send','Weixin\WxController@send');
//网页授权
Route::get('/weixin/getu', 'Weixin\WxController@getu');
Route::get('/weixin/sign','Weixin\WxController@sign');             //微信签到
//获取access_token
Route::get('/TmpUser/getaccesstoken','Weixin\TmpUserController@getaccesstoken');
Route::get('/TmpUser/tmper','Weixin\TmpUserController@tmper');
Route::get('/goods/goodsdetail/{goods_id?}','Weixin\WxController@goodsdetail');



//考试
/*获取access_token*/
Route::get('/exam/getaccesstoken','Weixin\WeixinController@getaccesstoken');
/*网页授权*/
Route::get('/exam/getu', 'Weixin\WeixinController@getu');
