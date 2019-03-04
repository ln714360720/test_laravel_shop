<?php

namespace App\Jobs;

use App\Models\CrowdfundingProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\OrderService;
use App\Models\Order;
// ShouldQueue 代表此任务需要异步执行
class RefundCrowdfundingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $crowdfunding;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CrowdfundingProduct $product)
    {
        $this->crowdfunding=$product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //众筹状态不是失败则不执行退款
        if($this->crowdfunding->status !==CrowdfundingProduct::STATUS_FAIL){
            return ;
        }
        //查询出所有参与了此众筹的订单
        $orderService=app(OrderService::class);//注入到容器里
        Order::query()
            //订单类型为众筹商品的订单
            ->where('type',Order::TYPE_CROWDFUNDING)
            ->whereNotNull('paid_at')
            //查询订单里的所有商品信息
            ->whereHas('items', function ($query)  {
                $query->where('product_id',$this->crowdfunding->product_id);
            })->get()->each(function (Order $order) use($orderService){
                //todo 退款逻辑
                $orderService->_refundOrder($order);
            });
    }
    
}
