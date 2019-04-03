<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SeckillProduct extends Model
{
    //
    protected $fillable=['start_at','end_at'];
    protected $dates=['start_at','end_at'];
    /**定义与商品的关联关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    //定义一个名is_before_start的访问器,当前时间早于秒杀开始时间返回true
    public function getIsBeforeStartAttribute()
    {
        return Carbon::now()->lt($this->start_at);
    }
    //定义一个名为is_after_end的访问器,当前时间晚于秒杀的结束时间返回true
    public function getIsAfterEndAttribute(){
        return Carbon::now()->gt($this->end_at);
    }
}
