<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
//
    function CartesianProduct($sets){

        // 保存结果
        $result = array();
//        dd(count($sets));//返回4
        // 循环遍历集合数据
        for($i=0,$count=count($sets); $i<$count-1; $i++){

            // 初始化 先把第一个数组拿出来,再和别的数组进行比较
            if($i==0){
                $result = $sets[$i];
            }
            // 保存临时数据
            $tmp = array();

            // 结果与下一个集合计算笛卡尔积
            foreach($result as $res){
                foreach($sets[$i+1] as $set){
                    $tmp[] = $res.$set;
                }
            }

            // 将笛卡尔积写入结果
            $result = $tmp;

        }

        return $result;

    }
   

// 定义集合
    $sets = array(
        array('白色','黑色','红色'),
        array('透气','防滑'),
        array('37码','38码','39码'),
        array('男款','女款')
    );
    dump($sets);
    $result = CartesianProduct($sets);
    dd($result);
//    return view('welcome');
//	dd(app()->make('files')->get(__DIR__.'/api.php'));
    //return view('welcome');
    //这是一个测试
});


