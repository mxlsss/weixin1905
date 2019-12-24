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
Route::get('info',function(){
    phpinfo();
});


Route::get('/wx','Weixin\WeixinController@wx');
Route::post('/wx','Weixin\WeixinController@receiv');
Route::get('/wx/picture','Weixin\WeixinController@picture');
Route::get('/caidan','Weixin\WeixinController@caidan');


//VOTE 投票
Route::get('/vote','Weixin\VoteController@index');

//群发
Route::get('/quefa','Weixin\VoteController@quefa');

//二维码
Route::get('/QR','Weixin\VoteController@QR');

//商场
Route::get('/shouye','Index\IndexController@index');
Route::get('/newYear','Index\IndexController@newYear');

