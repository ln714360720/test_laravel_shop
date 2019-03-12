<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstallmentsController extends Controller
{
    //
    
    public function index(Request $request)
    {
        $installments=Installment::query()
            ->where('user_id',$request->user()->id)
            ->paginate(10);
        return view('installments.index',compact('installments'));
    }
    //显示详情页面
    public function show(Installment $installment){
      $this->authorize('own',$installment);
        //取出当前分期付款的所有的还款计划,并按还款顺序
        $items=$installment->items()->orderBy('sequence')->get();
        
        return view('installments.show',[
            'installment'=>$installment,
            'items'=>$items,
            'nextItem'=>$items->where('paid_at',null)->first()
        ]);
    }
    
    public function payByAlipay(Installment $installment)
    {
        if($installment->order->closed){
            throw new InvalidRequestException('对应的商品订单已关闭');
        }
        
        if($installment->status ===Installment::STATUS_FINISHED){
            throw new InvalidRequestException('该分期订单已结清');
        }
        //获取当前分期付款最近的一笔未支付的还款计划
        if(!$nexItem=$installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
           
            throw new InvalidRequestException('该分期已经还清');
        }
        //调用支付宝的网页支付
        return app('alipay')->web([
           //支付订单叼使用分期流水号码+ 还款计划号编号
            'out_trade_no'=>$installment->no.'_'.$nexItem->sequence,
            'total_amount'=>$nexItem->total,
            'subject'=>'支付测试账号的分期订单:'.$installment->no,
            //这是的notify_url和return_url 可能覆盖在APpServiceProvide中的
            'notify_url'=>ngrok_url('installment.alipay.notify'),
            'return_url'=>route('installment.alipay.return'),
        ]);
    }
    //支付宝的两个回调,前端回调,后端回调
    public function alipayReturn()
    {
        try{
            app('alipay')->verify();
            }catch(\Exception $e){
                return view('pages.error',['msg'=>'数据不正确']);
            }
            return view('pages.success',['msg'=>'付款成功']);
    }
    //支付宝后端回调
    public function alipayNotify()
    {
        //验证支付宝数据是否正确
        $data=app('alipay')->verify();
        //如果订单状态不是成功或者结束,则不走后面的程序
        if(!in_array($data->trade_status, ['TRADE_SUCCESS','TRADE_FINISHED'])){
            return app('alipay')->success();
        }
        /**
         * 拉起支付时使用的支付订单号是由分期水号+ 还款计划编号组成的
         * 因此可以通过支付订单号来还原出这笔还款是哪个分期付款的哪个还款计划
         *
         */
        if($this->paid($data->out_trade_no, 'alipay', $data->trade_no)){
            return app('alipay')->success();
        }
        
        return 'fail';
    }
    //微信支付
    public function payByWechat(Installment $installment)
    {
        if($installment->order->closed){
            throw new InvalidRequestException('对应的商品订单已关闭');
        }
        if($installment->status===Installment::STATUS_FINISHED){
            throw new InvalidRequestException('该分期订单已结清');
        }
        if(!$nextItem=$installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
            throw new InvalidRequestException('该分期订单已结清');
        }
        $wechatOrder=app('wechat_pay')->scan([
            'out_trade_no'=>$installment->no.'_'.$nextItem->sequence,
            'total_fee'=>$nextItem->total*100,
            'body'=>'支付测试 订单号:'.$installment->no,
            'notify_url'=>ngrok_url('installment.wechat.notify'),
        ]);
        //把要转换字符串作为qrcode参数
        $qrCode=new QrCode($wechatOrder->code_url);
        return response($qrCode->writeString(),200,['Content-Type'=>$qrCode->getContentType()]);
    }
    //微信支付回调
    public function wechatNotify(){
        $data=app('wechat_pay')->verify();
        if($this->paid($data->out_trade_no, 'wechat', $data->transaction_id)){
            return app('wechat_pay')->success();
        }
        return 'fail';
    }
    
    protected function paid($outTradeNo,$paymentMethod,$paymentNo)
    {
        list($no,$sequence)=explode('_', $outTradeNo);
        //根据分期流水号查询对应的分期记录,
        if(!$installment=Installment::where('no',$no)->first()){
            return 'fail';
        }
        //根据还款计划编号查询对应的还款计划
        if(!$item=$installment->items()->where('sequence',$sequence)->first()){
            return 'fail';
        }
        //如果这个还款计划的支付状态是已支付,则告诉支付宝此订单已完成
        if($item->paid_at){
            return app('alipay')->success();
        }
        //使用事务,保证数据一致性
        \DB::transaction(function () use ($paymentNo,$paymentMethod,$no,$installment,$item){
            //更新对应的还款
            $item->update([
                'paid_at'=>Carbon::now(),
                'payment_method'=>$paymentMethod,//支付方式
                'payment_no'=>$paymentNo,//支付宝订单号
            ]);
            //如果这是第一笔还款
            if($item->sequence ===0){
                //将分期付款的状态改为还款中
                $installment->update([
                    'status'=>Installment::STATUS_REPAYING
                ]);
                //将分期侍对应的商品订单状态改为已支付
                $installment->order->update([
                    'paid_at'=>Carbon::now(),
                    'payment_method'=>'installment',//支付方式为分期付款
                    'payment_no'=>$no,//支付订单号为分期付款的流水号
                ]);
                //支付完成后触发订单已支付后的事件
                event(new OrderPaid($installment->order));
            }
            //如果是最后一笔
            if($item->sequence===$installment->count-1){
                //将分期付款状态改为已结清
                $installment->update([
                    'status'=>Installment::STATUS_FINISHED
                ]);
            }
        });
        return true;
        
    }
    
    /**微信分期退款回调
     * @param Request $request
     */
    public function wechatRefundNotify(Request $request)
    {
        //给微信的失败响应
        $failXml='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        //校验微信回调参数
        $data=app('wechat_pay')->verify(null,true);
        //根据单号拆解出对应的商品退款单号及对应的还款计划序号
        list($no,$sequence)=explode('_', $data['out_trade_no']);
        $item=InstallmentItem::query()
            ->whereHas('installment', function ($query) use ($no) {
            $query->whereHas('order',function($query)use ($no){
                $query->where('refund_no',$no);//根据订单表的退款流水号找到对应还款计划
            });
                })->where('sequence',$sequence)->first();
        
        //如果没有找到
        if(!$item){
            return $failXml;
        }
        //如果退款成功
        if($data['refund_success']==='SUCCESS'){
            //将还款计划退款状态改成退款成功
            $item->update(['refund_status'=>InstallmentItem::REFUND_STATUS_SUCCESS]);
            //封装后
            $item->installment->refreshRefundStatus();
            //设定一个标志位
//            $allSuccess=true;
//            foreach ($item->installment->items as $item) {
//                if($item->paid_at && $item->refund_status !==InstallmentItem::REFUND_STATUS_SUCCESS){
//                    $allSuccess=false;
//                    break;
//                }
//            }
//         //   如果所有的退款都成功,则将对应商品订单的退款状态改为退款成功
//            if($allSuccess){
//                $item->installment->order->update([
//                    'refund_status'=>Order::REFUND_STATUS_SUCCESS
//                ]);
//            }
        }else{
            $item->update(['refund_status'=>InstallmentItem::REFUND_STATUS_FAILED]);
        }
        return app('wechat_pay')->success();
    }
    
}
