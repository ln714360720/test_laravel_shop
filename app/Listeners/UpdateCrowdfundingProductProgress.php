<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class UpdateCrowdfundingProductProgress implements ShouldQueue
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
        $order=$event->getOrder();
        //如果订单类型不是众筹商品订单,无需处理
        if($order->type !==Order::TYPE_CROWDFUNDING){
            return ;
        }
//        dd($order->items);//这是一个items数组可有多个值,只取第一个
        $crowdfunding=$order->items[0]->product->crowdfunding;
        $data=Order::query()
            //查出订单类型为众筹订单
            ->where('type',Order::TYPE_CROWDFUNDING)
            //并且是已支付的
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) use ($crowdfunding) {
                $query->where('product_id',$crowdfunding->product_id);
            })->first([
               //取出订单总金额
                DB::raw('sum(total_amount) as total_amount'),
                DB::raw('count(distinct(user_id)) as user_count'),
            ]);
            $crowdfunding->update([
               'total_amount'=>$data->total_amount,
               'user_count'=>$data->user_count,
            ]);
    }
}
