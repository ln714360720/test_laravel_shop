<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /** 跳转到支付宝支付页面
     * @param Order   $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByAlipay(Order $order,Request $request)
    {
        //判断是否是当前用户
        $this->authorize('own',$order);
        //判断订单是否有效
        if($order->paid_at||$order->closed){
            throw  new InvalidRequestException('订单状态不正确');
        }
        //调用支付宝见面支付
        $data=[
            'out_trade_no'=>$order->no,
            'total_amount'=>$order->total_amount,//订单金额, 单位元,支持小数点后两位
            'subject'=>'支付测试订单:'.$order->no,
            'timeout_express'=>'1m',
        ];
        return app('alipay')->web($data);
    }
    
    /**支付宝支付成功后的前端页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function alipayReturn()
    {
        try{
            app('alipay')->verify();
        }catch (\Exception $e){
            return view('pages.error',['msg'=>'数据不正确!']);
        }
        return view('pages.success',['msg'=>'付款成功']);
    }
    
    /** 支付宝异步通知
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function alipayNotify()
    {
        //校验数据
        $data=app('alipay')->verify();
        //如果订单状态不是成功或者是结束,则不走后续的逻辑
        if(!in_array($data->trade_status, ['TRADE_SUCCESS','TRADE_FINISHED'])){
            return app('alipay')->success();
        }
        //通过out_trade_no 拿到订单流水号,并在数据库中查询
        $order=Order::where('no',$data->out_trade_no)->first();
        if(!$order){
            return 'fail';
        }
        //如果这笔订单已经是支付状态,则返回 success
        if($order->paid_at){
            return app('alipay')->success();
        }
        $order->update([
            'paid_at'=>Carbon::now(),//支付时间
            'payment_method'=>'alipay',
            'payment_no'=>$data->trade_no,//支付宝订单号
        ]);
        $this->afterPaid($order);
        return app('alipay')->success();
    }
    
    /**触发微信支付
     * @param Order   $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByWechat(Order $order, Request $request)
    {
        //验证权限
        $this->authorize('own',$order);
        //校验订单状态
        if($order->paid_at|| $order->closed){
            throw new InvalidRequestException('订单状态不正确');
        }
        //scan方法为微信扫码支付
        $data=[
            'out_trade_no'=>$order->no,
            'total_fee'=>$order->total_amount*100,//微信支付的金额单位是分
            'body'=>'支付测试订单:'.$order->no,
        ];
        $wechatOrder= app('wechat_pay')->scan($data);
        //创建二维码:
        $qrCode=new QrCode($wechatOrder->code_url);
        return response($qrCode->writeString(),200,['Content-Type'=>$qrCode->getContentType()]);
    
    }
    
    /** 微信异步通知
     * @return string
     */
    public function wechatNotify()
    {
        //校验回调是否正确
        $data=app('wechat_pay')->verify();
        $order=Order::where('no',$data->out_trade_no)->first();
        //如果不存在则告知微信支付
        if(!$order){
            return 'fail';
        }
        //订单已支付
        if($order->paid_at){
            return app('wechat_pay')->success();
        }
        //将订单标记为已支付
        $order->update([
           'paid_at'=>Carbon::now(),
           'payment_method'=>'wechat',
           'payment_no'=>$data->transaction_id
        ]);
        $this->afterPaid($order);
        return app('wechat_pay')->success();
    }
    protected function afterPaid(Order $order){
        event(new OrderPaid($order));
    }
    //微信退款异步通知处理结果
    public function wechatRefundNotify()
    {
        //给微信返回的失败响应
        $xml='<xml>
  <return_code><![CDATA[FAIL]]></return_code>
  <return_msg><![CDATA[FAIL]]></return_msg>
</xml>';
        //验证微信返回的数据
        $data=app('wechat_pay')->verify(null,true);
        //没有找到对应的订单,原则上不可能
        if(!$order=Order::where('no',$data['out_trade_no'])->first()){
            return $xml;
        }
        
        if($data['refund_status']==='SUCCESS'){
            //退款成功
            $order->update([
               'refund_status'=>Order::REFUND_STATUS_SUCCESS,
            ]);
        }else{
            //退款失败,写入到extra字段里
            $extra=$order->extra;
            $extra['refund_fail_code']=$data['refund_status'];
            $order->update([
               'refund_status'=>Order::REFUND_STATUS_FAILED,
               'extra'=>$extra,
            ]);
        }
        return app('wechat_pay')->success();
    }
    //分期付款接口
    public function payByInstallment(Order $order ,Request $request){
        //判断订单是否属于当前用户
        $this->authorize('own',$order);
        //订单已支付或者已关闭
        if($order->paid_at||$order->closed){
            throw new InvalidRequestException('订单状态不正确');
        }
        //订单不满员最低分期要求
        if($order->total_amount< config('app.min_installment_amount')){
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }
        //验证用户提交的还款月数,数据值必须是我们配置好费率的期数
        $this->validate($request, [
            'count'=>[
                'required',
                Rule::in(array_keys(config('app.installment_fee_rate'))),
            ],
        ]);
       
        //删除同一个订单发起过其他的状态是未支付的分期付款,避免同一人订单有多个分期付款
        Installment::query()->where('order_id',$order->id)
            ->where('status',Installment::STATUS_PENDING)->delete();
        $count=$request->input('count');
        //创建一个新的分期付款对象
        $installment=new Installment([
            //总本金 商品的订单总金额
            'total_amount'=>$order->total_amount,
            'count'=>$count,//分期期数
            'fee_rate'=>config('app.installment_fee_rate')[$count],//相应的费率
            'fine_rate'=>config('app.installment_fine_rate'),//逾期费率
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();
        //第一期还款日期为明天凌晨0点
        $dueDate=Carbon::tomorrow();
        //计算每一期的本金
        $base=big_number($order->total_amount)->divide($count)->getValue();
        //计算每一期的手续费
        $fee=big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
        //根据用户选择的还款期数,创建对应数量的还款计划
        for ($i=0;$i<$count;$i++){
            //最后一期的本金需要用总本金送去前面的本金
            if($i===$count-1){
                //最后一期的本金属需要总本金减去前面几期本金
                $base=big_number($order->total_amount)->subtract(big_number($base)->multiply($count-1));
            }
            $installment->items()->create([
                'sequence'=>$i,
                'base'=>$base,
                'fee'=>$fee,
                'due_date'=>$dueDate
            ]);
            //还款截止日期加30天
            $dueDate=$dueDate->copy()->addDays(30);//由于carbon对象使用的变量是引用变量,导致时间不会变,办法,重新复制一份
        }
        return $installment;
    }
    
}
