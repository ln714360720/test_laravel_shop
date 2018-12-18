<?php

use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products=Factory(\App\Models\Product::class,30)->create();
        //同时再创建3个sku,并把当前product_id 字段调为当前的商品id
        foreach ($products as $product){
            $skus=Factory(ProductSku::class,3)->create(['product_id'=>$product->id]);
            $product->update(['price'=>$skus->min('price')]);
        }
    }
}
