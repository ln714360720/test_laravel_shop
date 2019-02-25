<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Logger;
use Yansongda\Pay\Pay;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        view()->share('lnssm','这是一个lnssm测试');这样写视图里可以共享这一个变量,而不用每个页面云传递,可以使用view对象提供的share方法,在页面里只需要用$lnssm就可以显示
        Schema::defaultStringLength(191);
        // 只在本地开发环境启用 SQL 日志
        if (app()->environment('local')) {
            \DB::listen(function ($query) {
                \Log::info(Str::replaceArray('?', $query->bindings, $query->sql));
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //向容器里注入支付实例
        $this->app->singleton('alipay',function(){
            $config=config('pay.alipay');
            //判断当前是否是在线上,还是开发环境
            if(app()->environment() !=='production'){
                $config['mode']='dev';
                $config['log']['level']=Logger::DEBUG;
                $config['notify_url']=route('payment.alipay.notify');
                $config['return_url']=route('payment.alipay.return');
            }else{
                $config['log']['level']=Logger::WARNING;
                $config['notify_url']=route('payment.alipay.notify');
                $config['return_url']=route('payment.alipay.return');
            }
            //调用 Yansongda\Pay 来创建一个支付宝对象
            return Pay::alipay($config);
        });
        $this->app->singleton('wechat_pay',function (){
            $config=config('pay.wechat');
            if(app()->environment() !=='production'){
                $config['log']['level']=Logger::DEBUG;
            }else{
                $config['log']['level']=Logger::WARNING;
                $config['notify_url']=route('payment.wechat.refund_notify');
            }
            return Pay::wechat($config);
        });
    }
}
