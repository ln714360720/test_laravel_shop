<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $order;
    public function __construct(Order $order)
    {
        $this->order=$order;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //如果订单商品不是分期付款,订单未支付,订单退款状态不是退款中的,则不执行
        if($this->order->payment_method !=='installment'||!$this->order->paid_at||$this->order->refund_status !==Order::REFUND_STATUS_PROCESSING){
            return ;
        }
        //找不到对应的分期付款订单,原则上不可能
        if(!$installment=Installment::query()->where('order_id',$this->order->id)->first()){
            return;
        }
        //遍历对应分期付款的所有还款计划
        foreach ($installment->items as $item) {
            //如果还款计划未支付,或者退款状态为退款成功或退款中,则跳过
            if(!$item->paid_at||in_array($item->refund_status, [InstallmentItem::REFUND_STATUS_SUCCESS,InstallmentItem::REFUND_STATUS_PROCESSING])){
                continue;
            }
            //调用具体的退款逻辑
            try{
                $this->refundInstallmentItem($item);
                }catch(\Exception $e){
                    \Log::warning('分期退款失败'.$e->getMessage(),['installment_item_id'=>$item->id]);
                }
        }
        //封装后的
        $installment->refreshRefundStatus();
        //设定一个分部退款成功的标志
//        $allSuccess=true;
//        //再次遍历所有还款计划
//        foreach ($installment->items as $item) {
//            //如果该还款计划已经还款,但是退款状态不是成功
//            if($item->paid_at && $item->refund_status !==InstallmentItem::REFUND_STATUS_SUCCESS){
//                $allSuccess=false;
//                break;
//            }
//        }
//        //如果所有退款成功,则将对应商品订单的退款状态修改为退款成功
//        if($allSuccess){
//            $this->order->update(['refund_status'=>Order::REFUND_STATUS_SUCCESS]);
//        }
    }
    //执行退款逻辑
    protected function refundInstallmentItem(InstallmentItem $item){
        //退款订单号使用商品订单的退款号与当前还款计划的序号拼接而成
        $refundNo=$this->order->refund_on.'_'.$item->sequence;
        //根据还款计划的支付方式执行对应的退款逻辑
        switch($item->payment_method){
                case 'wechat':
                    app('wechat_pay')->refund([
                       'transaction_id'=>$item->payment_no, //微信订单号
                        'total_fee'=>$item->total*100,//原订单金额
                        'refund_fee'=>$item->base*100,//要退款的订单金额,只退本金
                        'out_refund_no'=>$refundNo,//退款订单号
                        'notify_url'=>ngrok_url('installments.wechat.refund_notify'),//微信支付的退款结果并不是实时返回的,而是通过回调来通知
                    ]);
                    $item->update(['refund_status'=>InstallmentItem::REFUND_STATUS_PROCESSING]);
                    break;
                    case 'alipay':
                        $ret=app('alipay')->refund([
                            'trade_no'=>$item->payment_no,//使用支付宝交易号来退款
                            'refund_amount'=>$item->base,//只退本金
                            'out_request_no'=>$refundNo,//退款订单号
                        ]);
                        //根据支付宝的床,如果返回值里有sub_code字段说明退款失败
                    if($ret->sub_code){
                        $item->update([
                            'refund_status'=>InstallmentItem::REFUND_STATUS_FAILED
                        ]);
                    }else{
                        //将订单的退款状态标记为退款成功并保存退款订单号
                        $item->update([
                            'refund_status'=>InstallmentItem::REFUND_STATUS_SUCCESS
                        ]);
                    }
                    break;
                    default:
                    //原则上不可能出现,这个只是为了代码健壮性
                        throw new InternalException('未知订单支付方式:'.$item->payment_method);
                    break;
                
            }
    }
}
