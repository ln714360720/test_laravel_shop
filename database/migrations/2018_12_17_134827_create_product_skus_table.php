<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_skus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('sku名称');
            $table->string('description')->comment('sku描述');
            $table->decimal('price',10,2)->comment('sku价格');
            $table->unsignedInteger('stock')->comment('商品库存');
            $table->unsignedInteger('product_id')->comment('商品外键');
            $table->foreign('product_id')->references('id')->on("products")->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_skus');
    }
}
