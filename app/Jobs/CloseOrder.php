<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order,$delay)
    {
        $this->order=$order;
        $this->delay($delay);//设置延迟时间,delay()方法的参数代表多少秒之后执行
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //如果已经支付,则不需要关闭订单,直接退出
        if($this->order->paid_at){
            return;
        }
        //通过事务执行sql
        DB::transaction(function (){
            $this->order->update(['closed'=>true]);
            //循环遍历订单顺的商品sku,将订单中的数量加回到sku的库存中
            foreach ($this->order->items as $item){
               $res= $item->productSku->addStock($item->amount);
               
            }
        });
        
    }
}
