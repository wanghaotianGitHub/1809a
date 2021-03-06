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

Route::get('valid','WxController@valid');
Route::post('valid','WxController@wxEvent');
Route::any('accessToken','WxController@accessToken');
Route::any('menu','WxController@menu');
Route::any('openiddo','WxController@openiddo');
Route::any('give','WxController@give');  //用户授权
Route::any('code','WxController@code');  //code回调

Route::any('goodList','GoodsController@goodList');
Route::any('details','GoodsController@details');
Route::any('goodDetail','GoodsController@goodDetail');
Route::any('accessToken','GoodsController@accessToken');

Route::get('/goods/cache/{id?}', 'GoodsController@cacheGoods');      //缓存商品信息


