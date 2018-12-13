<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    /**
     * apiException constructor.
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    protected $arrData;
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null,$data=array())
    {
        $this->arrData=$data;
        parent::__construct($message, $code, $previous);
    }
    
    public function render()
    {
        $arr=[
            'status'=>$this->code,
            'msg'=>$this->message,
            'data'=>$this->arrData
        ];
       return response()->json($arr);
   }
}
