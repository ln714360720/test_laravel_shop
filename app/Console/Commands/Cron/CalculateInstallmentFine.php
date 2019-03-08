<?php

namespace App\Console\Commands\Cron;

use App\Models\Installment;
use App\Models\InstallmentItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateInstallmentFine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:calculate-installment-fine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算分期付款逾期费';

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
        InstallmentItem::query()
            ->with(['installment'])//预加载分期付款数据,防止n+1
            ->whereHas('installment', function ($query) {
                //对应的分期状态为还款中
                $query->where('status',Installment::STATUS_REPAYING);
            })->where('due_date','<=',Carbon::now())
            ->whereNull('paid_at')
            //使用chunkById避免一次性查太多记录
            ->chunkById(1000, function ($items){
                //遍历所有已逾期的还款计划
                foreach ($items as $item) {
                    //通过Carbon对象里的diffInDays()可出算出逾期几天
                    $overdueDays=Carbon::now()->diffInDays($item->due_date);
                    //计算本金与手续费之和
                    $base=big_number($item->base)->add($item->fee)->getValue();
                    //计算逾期费,在计算高精确度的运算时,应先执行让结果绝对值变大的数,否则会出现不一样的数据,因为数据库只保留了两位数,小数点后面的数会被舍弃
                    $fine=big_number($base)->multiply($overdueDays)
                    ->multiply($item->installment->fine_rate)
                    ->divide(100)
                    ->getValue();
                    //避免逾期费用高于本金与手续费之和,国家规定,
                    $fine=big_number($fine)->compareTo($base)===1?$base:$fine;
                    $item->update([
                        'fine'=>$fine
                    ]);
                }
            });
    }
}
