<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdfundingProduct extends Model
{
    //定论众筹状态
    const  STATUS_FUNDING='funding';
    const  STATUS_SUCCESS='success';
    const  STATUS_FAIL='fail';
    public static $statusMap=[
        self::STATUS_FUNDING=>'众筹中',
        self::STATUS_SUCCESS=>'众筹成功',
        self::STATUS_FAIL=>'众筹失败',
    ];
    protected $fillable=[
        'total_amount','target_amount','user_count','end_at','status'
    ];
    protected $dates=['end_at'];
    public $timestamps=false;
    //定义与商品的关联关系 一对一
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    //定义一个获取器,返回当前众筹进度
    public function getPercentAttribute()
    {
        //已筹金额除以目标金额
        $value=$this->attributes['total_amount'] / $this->attributes['target_amount'];
        return floatval(number_format($value*100,2,'.',''));
    }
}
