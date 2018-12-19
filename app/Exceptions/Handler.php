<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
class Handler extends ExceptionHandler
{
    
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        InvalidRequestException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
       if($exception instanceof ValidationException && request()->expectsJson()){
           $arr=[
               'status'=>-1,
               'msg'=>$exception->errors(),
               'data'=>array()
               
           ];
           return new JsonResponse($arr);
       }
       //处理laravel自带用户认证返回json格式的处理
//if ($exception instanceof  AuthenticationException && request()->expectsJson()){
//           $arr=[
//               'status'=>401,
//               'msg'=>$exception->getMessage(),
//               'data'=>array()
//
//           ];
//           return new JsonResponse($arr);
//       }
//
        return parent::render($request, $exception);
    }
    
}
