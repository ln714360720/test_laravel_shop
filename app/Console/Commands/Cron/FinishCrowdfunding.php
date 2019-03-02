<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        CrowdfundingProduct::query()
            //众筹结束时间早于当前时间
        ->where('end_at','<=',Carbon::now())
        ->where('status',CrowdfundingProduct::STATUS_FUNDING)
            ->get()->each(function(CrowdfundingProduct $crowdfunding){
                //如果众筹目标金额大于实际金额
                if($crowdfunding->target_amount > $crowdfunding->total_amount){
                    //调用众筹失败逻辑
                    $this->crowdfundingFail($crowdfunding);
                }else{
                    //用户众筹成功逻辑
                    $this->crowdfundingSuccessed($crowdfunding);
                }
            });
    }
    protected function crowdfundingFail(CrowdfundingProduct $crowdfunding){
        //将众筹状态改为失败状态
        $crowdfunding->update([
            'status'=>CrowdfundingProduct::STATUS_FAIL,
        ]);
        dispatch(new RefundCrowdfundingOrders($crowdfunding));
    }
    protected function crowdfundingSuccessed(CrowdfundingProduct $crowdfunding){
        //只更新众筹状态
        $crowdfunding->update([
           'status'=>CrowdfundingProduct::STATUS_SUCCESS,
        ]);
    }
}
