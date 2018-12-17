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
}
