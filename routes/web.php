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



Route::get("/",'PagesController@root')->name('root');//name为路由命名,为生成url和重定向提供方便
//自带的登录注册  路由信息在vendor/laravel/framework/src/Illuminate/Routing/Router.php里的auth方法里
Auth::routes();//

Route::group(['middleware'=>'auth'],function (){
    //验证邮箱路由
   Route::get("email_verify_notice",'PagesController@emailVerifyNotice')->name('email_verify_notice');
   
});

