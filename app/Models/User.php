<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','email_verified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
   protected $casts=[
       'email_verified'=>'boolean',//这个字段呢,定义了属性转化 被转化成原生类型
   ];
    
    /**定义用户与收货地址的关联关系
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
   public function addresses(){
       return $this->hasMany(UserAddress::class,'','id');
   }
    
    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class,'user_favorite_products')
            ->withTimestamps()->orderBy('user_favorite_products.created_at','desc');
   }
}
