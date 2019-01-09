<?php

namespace App\Exceptions;
use Exception;
use Illuminate\Http\Request;
class CouponCodeUnavailableException extends Exception
{
    //
    /**
     * CouponCodeUnavailableException constructor.
     */
    public function __construct($message,int $code=403)
    {
        parent::__construct($message,$code);
    }
    
    public function render(Request $request)
    {
        //如果用户是通过api请求的,则返回json格式的错误信息
        if($request->expectsJson()){
            return response()->json(['msg'=>$this->message],$this->code);
        }
        //否则返回一页,并带上错误信息
        return redirect()->back()->withErrors(['coupon_code'=>$this->message]);
    }
    
}
