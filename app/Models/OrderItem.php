<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable=[
        'amount','price','rating','review','review_at'
    ];
    protected $dates=['review_at'];
    public $timestamps=false;
    //定义与商品的关联关系
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    //定义与sku的关联关系
    public function productSku()
    {
        return $this->belongsTo(ProductSku::class);
    }
    //定义与订单的关联关系
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
