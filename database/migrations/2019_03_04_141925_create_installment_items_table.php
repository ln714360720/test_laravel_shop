<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstallmentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('installment_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('installment_id')->comment('installment外键');
            $table->foreign('installment_id')->references('id')->on('installments')->onDelete('cascade');
            $table->unsignedInteger('sequece')->comment('还款顺序编号');
            $table->decimal('base',2)->comment('当期本金');
            $table->decimal('fee',2)->comment('当期手续费');
            $table->decimal('fine',2)->comment('当期逾期费');
            $table->dateTime('due_date')->comment('还款截止日期');
            $table->dateTime('paid_at')->comment('还款日期');
            $table->string('payment_method')->comment('还款支付方式');
            $table->string('payment_no')->comment('还款支付平台订单号');
            $table->string('refund_status')->comment('退款状态')->default(\App\Models\InstallmentItem::REFUND_STATUS_PENDING);
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
        Schema::dropIfExists('installment_items');
    }
}
