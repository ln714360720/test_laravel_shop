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


use function foo\func;

Route::get("/",'PagesController@root')->name('root');//name为路由命名,为生成url和重定向提供方便
//自带的登录注册  路由信息在vendor/laravel/framework/src/Illuminate/Routing/Router.php里的auth方法里
Auth::routes();//

Route::group(['middleware'=>'auth'],function (){
    //验证邮箱路由
   Route::get("email_verify_notice",'PagesController@emailVerifyNotice')->name('email_verify_notice');
   Route::get('email_verification/verify','EmailVerificationController@verify')->name('email_verification.verify');
   Route::get('/email_verification/send', 'EmailVerificationController@send')->name('email_verification.send');//用户主动发送邮件
    //email_verified这个中间件是自己定义的,只有当邮箱验证通过了,才执行以下方法
    Route::group(['middleware'=>'email_verified'],function (){
    
    });
});

