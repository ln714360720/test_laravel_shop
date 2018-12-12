<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class AjaxRequestException extends AuthenticationException
{
    /**
     * AjaxRequestException constructor.
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    public function __construct(string $message = "请先登录", int $code = 401)
    {
        parent::__construct($message);
    }
    
    public function render(Request $request)
    {
        if($request->expectsJson()){
            return response()->json(['msg'=>'请先登录']);
        }
    }
}
