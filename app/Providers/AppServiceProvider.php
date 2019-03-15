<?php

namespace App\Providers;

use App\Http\viewComposers\CategoryTreeComposer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Logger;
use Yansongda\Pay\Pay;
use Elasticsearch\ClientBuilder as ESClientBuilder;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Carbon::setLocale('zh');//carbon设置为中文

        Schema::defaultStringLength(191);
        //定义好 ViewComposer 之后我们还需要告诉 Laravel 要把这个 ViewComposer 应用到哪些模板文件里：
        view()->composer(['products.index','products.show'],CategoryTreeComposer::class);
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
                $config['notify_url']=ngrok_url('payment.alipay.notify');
                $config['return_url']=route('payment.alipay.return');
            }else{
                $config['log']['level']=Logger::WARNING;
                $config['notify_url']=ngrok_url('payment.alipay.notify');
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
                $config['notify_url']=ngrok_url('payment.wechat.refund_notify');
            }
            return Pay::wechat($config);
        });
        //注册一个人名为es的实例
        $this->app->singleton('es', function () {
            //从配置文件读取Elasticsearch服务器列表
            $builder=ESClientBuilder::create()->setHosts(config('database.elasticsearch.hosts'));
            //如果是开发环境
            if(app()->environment()==='local'){
                //配置日志,Elasticsearch 的请求和响应都写日志里
                $builder->setLogger(app('log')->driver());
            }
            return $builder->build();
        });
    }
}
