<?php

/**此方法会将当前请求的路由名称转换为css类名称,作用是允许我们针对某个碳估页面样式定制
 * @return mixed
 */
function route_class(){
    return str_replace('.', '-', Route::currentRouteName());
}