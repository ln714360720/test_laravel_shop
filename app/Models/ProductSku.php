<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    //
    protected $fillable=[
        'title','description','price','stock'
    ];
    //关联product模型
    public function product(){
        return $this->belongsTo(Product::class,'product_id','id');
    }
    public function decreaseStock($amount)
    {
        if($amount<0){
            throw  new InternalException('减库存不可小于0');
        }
        return  $this->newQuery()->where('id',$this->id)->where('stock','>=',$amount)->decrement('stock',$amount);
    }
    //添加库存
    public function addStock($amount)
    {
        if($amount<0){
            throw new InternalException('加库存不可小于0');
        }
      return  $this->increment('stock',$amount);
    }
}
