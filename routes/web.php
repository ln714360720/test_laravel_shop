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
Route::redirect("/",'/products')->name('root');//name为路由命名,为生成url和重定向提供方便
//自带的登录注册  路由信息在vendor/laravel/framework/src/Illuminate/Routing/Router.php里的auth方法里
Auth::routes();//

Route::group(['middleware'=>'auth'],function (){

    //验证邮箱路由
   Route::get("email_verify_notice",'PagesController@emailVerifyNotice')->name('email_verify_notice');
   Route::get('email_verification/verify','EmailVerificationController@verify')->name('email_verification.verify');
   Route::get('/email_verification/send', 'EmailVerificationController@send')->name('email_verification.send');//用户主动发送邮件
    //email_verified这个中间件是自己定义的,只有当邮箱验证通过了,才执行以下方法
    Route::group(['middleware'=>'email_verified'],function (){
        Route::get('user_addresses','UserAddressesController@index')->name('user_addresses.index');
        Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
        Route::post('user_addresses','UserAddressesController@store')->name('user_addresses.store');
        Route::get('user_addresses/{user_address}','UserAddressesController@edit')->name('user_addresses.edit');
        Route::put('user_addresses/{user_address}','UserAddressesController@update')->name('user_addresses.update');
        Route::delete('user_addresses/{user_address}','UserAddressesController@destroy')->name('user_addresses.destroy');
        //用户收藏/取消收藏路由
        Route::post('products/{product}/favorite','ProductsController@favor')->name('products.favor');
        Route::delete('products/{product}/favorite','ProductsController@disfavor')->name('products.disfavor');
        Route::get('products/favorites','ProductsController@favorites')->name('products.favorites');
        //添加购物车
        Route::post('cart','CartController@add')->name('cart.add');
        Route::get('cart','CartController@index')->name('cart.index');
        Route::delete('cart/{sku}','CartController@remove')->name('cart.remove');
        //提交定单
        Route::post('orders',"OrdersController@store")->name('orders.store');
        //订单列表
        Route::get('orders',"OrdersController@index")->name('orders.index');
        //查看订单
        Route::get('orders/{order}','OrdersController@show')->name('order.show');
        //订单支付
        Route::get('payment/{order}/alipay','PaymentController@payByAlipay')->name('payment.alipay');
        //支付宝支付成功后回调 页面显示
        Route::get('payment/alipay/return','PaymentController@alipayReturn')->name('payment.alipay.return');
        //调起微信支付页面
        Route::get('payment/{order}/wechat','PaymentController@payByWechat')->name('payment.wechat');
    });
    Route::post('orders/{order}/received','OrdersController@received')->name('orders.received');
//    订单评价
    Route::get('orders/{order}/review','OrdersController@review')->name('orders.review.show');
    Route::post('orders/{order}/review','OrdersController@sendReview')->name('orders.review.store');
    //用户申请退款
    Route::post('orders/{order}/apply_refund','OrdersController@applyRefund')->name('order.apply_refund');
    //获取优惠码
    Route::get('coupon_code/{code}','CouponCodesController@show')->name('coupon_code.show');
    //众筹商品下单处理
    Route::post('crowdfunding_orders','OrdersController@crowdfunding')->name('crowdfunding_orders.store');
    //分期付款路由
    Route::post('payment/{order}/installment','PaymentController@payByInstallment')->name('payment.installment');
    //前台用户展示分期列表
    Route::get('installments','InstallmentsController@index')->name('installments.index');
});
//支付宝支付成功后返回异步通知服务器 post,需要解决csrf问题,需要在中间件里排除它
Route::post('payment/alipay/notify','PaymentController@alipayNotify')->name('payment.alipay.notify');
//微信异步通知
Route::post('payment/wechat/notify','PaymentController@wechatNotify')->name('payment.wechat.notify');
//微信退款异步通知
Route::post('payment/wechat/refund_notify','PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');

Route::get('products','ProductsController@index')->name("products.index");
Route::get('products/{product}','ProductsController@show')->name("products.show");
//配置laravel-admin图片上传路由
Route::post('/admin/upload','\App\Admin\Controllers\UploadController@index');

