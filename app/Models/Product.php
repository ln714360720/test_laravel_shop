<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable=[
        'title','description','image','on_sale',
        'rating','sold_cont','review_count','price'
    ];
    protected $casts=[
        'on_sale'=>'boolean'
    ];
    //与sku关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class,'','id');
    }
}
