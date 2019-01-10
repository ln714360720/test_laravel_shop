<?php

use App\Models\Product;
use Faker\Generator as Faker;

$factory->define(App\Models\OrderItem::class, function (Faker $faker) {
    //从数据库里随机取出一条商品
    $product=Product::query()->where('on_sale',true)->inRandomOrder()->first();
    //从该商品里的sku随机取出一条
    $sku=$product->skus()->inRandomOrder()->first();
    
    return [
        'amount'=>random_int(1, 5),
        'price'=>$sku->price,
        'rating'=>null,
        'review'=>null,
        'review_at'=>null,
        'product_id'=>$product->id,
        'product_sku_id'=>$sku->id
    ];
});
