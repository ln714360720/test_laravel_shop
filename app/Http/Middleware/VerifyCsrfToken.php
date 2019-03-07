<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'payment/alipay/notify',
        'payment/wechat/notify',
        'payment/wechat/refund_notify',
        'admin/upload',//配置laravel-admin使用自带的ckeditor上传路径
        'installments/alipay/notify',//支付宝分期付款回调路径
        'installments/wechat/notify',//微信分期支付回调路径
    ];
}
