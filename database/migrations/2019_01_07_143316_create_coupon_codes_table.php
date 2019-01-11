<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('优惠券标题');
            $table->string('code')->unique()->comment('优惠码');
            $table->string('type')->comment('类型,支付固定金额和百分比');
            $table->decimal('value',10,2)->comment('折扣值');
            $table->unsignedInteger('total')->comment('全站可以兑换的数据');
            $table->unsignedInteger('used')->default(0)->comment('已使用的优惠券');
            $table->decimal('min_amount',10,2)->comment('使用优惠券的最低金额');
            $table->dateTime('not_before')->comment('这个时间之前不可用')->nullable();
            $table->dateTime('not_after')->comment('这个时间之后不可用')->nullable();
            $table->tinyInteger('enabled')->comment('优惠券是否有效');
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
        Schema::dropIfExists('coupon_codes');
    }
}
