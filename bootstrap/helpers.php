<?php

/**此方法会将当前请求的路由名称转换为css类名称,作用是允许我们针对某个碳估页面样式定制
 * @return mixed
 */
function route_class(){
    return str_replace('.', '-', Route::currentRouteName());
}
function ngrok_url($routeName,$paramters=[]){
    //开发环境,并且配置了ngrok_url
    if(app()->environment('local')&& $url=config('app.ngrok_url')){
        //route的第三个参数代表绝对路径
        return $url.route($routeName,$paramters,false);
    }
    return route($routeName,$paramters);
}