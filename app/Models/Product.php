<?php

namespace App\Models;

use function foo\func;
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
        'title','description','image','on_sale','long_title',
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
    
    /**定义商品与商品属性的关联关系
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }
    
    public function getGroupedPropertiesAttribute()
    {
        return $this->properties->groupBy('name')->
            map(function ($properties){
           return $properties->pluck('value')->all();
        });
    }
    //Elasticsearch获取的对象转化为数组
    public function toESArray()
    {
        //只取出需要的字段
        $arr=array_only($this->toArray(), [
            'id','type','title','category_id','long_title','on_sale','rating',
            'sold_count','review_count','price'
        ]);
        //如果商品有类目,则category字段为类目名数组,否则为空字符串
        $arr['category']=$this->category?explode('-', $this->category->full_name):'';
        //类目的path字段
        $arr['category_path']=$this->category?$this->category->path:'';
        //strip_tags函数可以将html标签去除
        $arr['description']=strip_tags($this->description);
        //只取出需要的sku字段
        $arr['skus']=$this->skus->map(function (ProductSku $sku){
           return array_only($sku->toArray(), ['title','description','price']);
        });
        //只取出需要姝商品属性字段
        $arr['properties']=$this->properties->map(function (ProductProperty $property){
           return array_merge(array_only($property->toArray(), ['name','value']),['search_value'=>$property->name.':'.$property->value]);
        });
        return $arr;
    }
    //定义一个scope
    public function scopeByIds($query,$ids){
        return $query->whereIn('id',$ids)->orderByRaw(sprintf("find_in_set(id,'%s')",join(',', $ids)));
    }
}
