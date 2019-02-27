<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable=[
        'province','city','district','address','zip',
        'contact_name','contact_phone',
        'last_used_at'
    ];
    protected $dates=['last_used_at'];//这个意思呢,定义这个字段为日期的属性
    protected $appends=[
        'full_address'
    ];
    //定义关联关系
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');//第一个参数:关联的模型,第二个参数:本表要关联别一个模型的外键(简单来说就是本表对应user表的外键),第三个参数:本表的主键
    }
    
    //获取地址的全路径 ,这是访问器
    public function getFullAddressAttribute()
    {
        return "{$this->province}{$this->city}{$this->district}{$this->address}";
    }
}
