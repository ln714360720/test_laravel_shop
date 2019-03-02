<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    //定义常量区分是普通商品还是众筹商品
    const TYPE_NORMAL='normal';
    const TYPE_CROWDFUNDING='crowdfunding';
    public static $typeMap=[
        self::TYPE_CROWDFUNDING=>'众筹商品',
        self::TYPE_NORMAL=>'普通商品',
    ];
    //
    protected $fillable=[
        'title','description','image','on_sale',
        'rating','sold_count','review_count','price','type',
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
    //定义商品与分类关联
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    /**定义商品与众筹商品的关联关系,一对一关系
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }
}
