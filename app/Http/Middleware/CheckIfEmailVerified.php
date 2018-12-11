<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //如果是ajax请求,则返回json
        if(!$request->user()->email_verified){
            if($request->expectsJson()){
                return response()->json(['msg'=>'请先验证邮箱','status'=>400]);
            }
            return redirect(route('email_verify_notice'));
        }
       
        return $next($request);
    }
}
