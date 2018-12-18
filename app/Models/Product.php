<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    //
    protected $fillable=[
        'title','description','image','on_sale',
        'rating','sold_count','review_count','price'
    ];
    protected $casts=[
        'on_sale'=>'boolean'
    ];
    //与sku关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class,'','id');
    }
    //添加一个绝对访问器来修改图片的访问路径,因为数据库里存入的时storage/app/public 的相对路径,需要转化为绝对路径才可以
    public function getImageUrlAttribute()
    {
        if(Str::startsWith($this->attributes['image'], ['http://','https://'])){
            return $this->attributes['image'];
        }
        return Storage::disk('public')->url($this->attributes['image']);
    }
}
