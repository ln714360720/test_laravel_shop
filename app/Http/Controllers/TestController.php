<?php
/**
 * Created by PhpStorm.
 * User: xs
 * Date: 2018/12/4
 * Time: 13:01
 */

namespace App\Http\Controllers;

class TestController extends Controller
{
    
    public function add()
    {
        $this->thisTest();
        
    }
    
    
  
    public function thisTest()
    {
        
        if (true) {
            $str1 = '$test is  is test';
            $str = $str1;
            echo '' . $str . '';
        } else {
            $args = '$test is  this这是一个测试';
            dd($args);
        }
    }
    
    
}