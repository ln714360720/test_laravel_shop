<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductProperty extends Model
{
    //
    protected $fillable=['name','value'];
    public $timestamps=false;
    //定义与商品的关联关系
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
