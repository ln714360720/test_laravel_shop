<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->get('users','UsersController@index');
    $router->get('products','ProductsController@index');
    $router->get('products/create','ProductsController@create');
    $router->post('products','ProductsController@store');
    $router->get('products/{id}/edit','ProductsController@edit');
    $router->get('products/{id}','ProductsController@detail');
    $router->put('products/{id}','ProductsController@update');
    $router->get('orders',"OrdersController@index")->name('admin.orders.index');
    $router->get('orders/{order}','OrdersController@show')->name('admin.orders.show');
    $router->post('orders/{order}/ship','OrdersController@ship')->name('admin.orders.ship');
    $router->post('orders/{order}/refund','OrdersController@handleRefund')->name('admin.orders.handle_refund');
    $router->get('coupon_codes','CouponCodesController@index');
    $router->get('coupon_codes/create','CouponCodesController@create');//新建优惠
    $router->post('coupon_codes','CouponCodesController@store');//
    //编辑
    $router->get('coupon_codes/{id}/edit','CouponCodesController@edit');
    $router->put('coupon_codes/{id}','CouponCodesController@update');//更新优惠券
    //删除
    $router->delete('coupon_codes/{id}','CouponCodesController@destroy');
});
