<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\OrderItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
//implements ShouldQueue 代表监听器是异步执行的
class UpdateProductSoldCount implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        //从事件对象里取出对应的订单
        $order=$event->getOrder();
        //预加载商品数据,防止n+1
        $order->load('items.product');
        //遍历订单的商品
        foreach ($order->items as $item){
            $product=$item->product;//获取当前订单下商品
            //计算对应商品的销量
            $soldCount=OrderItem::query()->where('product_id',$product->id)
                ->whereHas('order',function ($query){
                    $query->whereNotNull('paid_at');//关联的订单状态是已支付
                })->sum('amount');
            $product->update([
               'sold_count'=>$soldCount,
            ]);
            
        }
        
    }
}
