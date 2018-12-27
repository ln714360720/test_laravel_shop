<?php

use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no')->unique();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('address')->comment('json格式的收货地址');
            $table->decimal('total_amount',10,2);
            $table->text('remark')->comment('订单备注');
            $table->dateTime('paid_at')->comment('支付时间')->nullable();
            $table->string('payment_method')->comment('支付方式')->nullable();
            $table->string('payment_no')->comment('支付平台订单号')->nullable();
            $table->string('refund_status')->comment('退款状态')->default(Order::REFUND_STATUS_PENDING);
            $table->string('refund_no')->comment('退款单号')->unique()->nullable();
            $table->tinyInteger('closed')->default(0)->comment('订单是否关闭');
            $table->tinyInteger('reviewed')->default(0)->comment('订单是否已评价');
            $table->string('ship_status')->comment('物流状态')->default(Order::SHIP_STATUS_PENDING);
            $table->text('ship_data')->comment('物流数据')->nullable();
            $table->text('extra')->comment('其他额外的数据')->nullable();
            
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
        Schema::dropIfExists('orders');
    }
}
