<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable=[
        'amount',
    ];
    public $timestamps=false;
    
    /**跟用户建立关联关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function productSku(){
        return $this->belongsTo(ProductSku::class);
    }
}
