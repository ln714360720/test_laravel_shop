<?php
/**
 * Created by PhpStorm.
 * User: xs
 * Date: 2018/12/25
 * Time: 15:26
 */

namespace App\Services;


use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    //普通商品下单
    public function store(User $user,UserAddress $userAddress,$remark,$items,CouponCode $coupon_code=null)
    {
        //如果传入了优惠券,则先检查是否可用
        if($coupon_code){
            $coupon_code->checkAvailable($user);
        }
        //开一个事务
        $order=DB::transaction(function () use ($user,$userAddress,$remark,$items,$coupon_code){
            //创建一个订单
            $order=new Order([
                'address'=>[
                    'address'       => $userAddress->full_address,
                    'zip'           => $userAddress->zip,
                    'contact_name'  => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone,
                    'type'=>Order::TYPE_NORMAL,
                ],
                'remark'=>$remark,
                'total_amount'=>0,
            ]);
            //订单关联到用户
            $order->user()->associate($user);
            //写入到数据库
            $order->save();
            $totalAmount=0;
            //遍历用户提交的sku信息
            foreach ($items as $data){
                $sku=ProductSku::find($data['sku_id']);
                //创建一个orderItem并直接与当前订单关联
                $item=$order->items()->make([
                    'amount'=>$data['amount'],
                    'price'=>$sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount +=$sku->price*$data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }
            //优惠券相关操作
            if($coupon_code){
                //总金额已经出来了,检查是否符合优惠券规则
                $coupon_code->checkAvailable($user,$totalAmount);
                //把订单修改成优惠后金额
                $totalAmount=$coupon_code->getAdjustedPrice($totalAmount);
                //将订单与优惠券关联
                $order->couponCode()->associate($coupon_code);
                //增加优惠券的用量,需判断返回值
                if($coupon_code->changeUsed()<=0){
                    throw new CouponCodeUnavailableException('优惠券已兑换完!');
                }
            }
            
            $order->update(['total_amount' => $totalAmount]);
            // 将下单的商品从购物车里移除
            $skuIds=collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            return $order;
        });
        //加入队列 未支付的订单30分钟后关闭
        dispatch(new CloseOrder($order, config('app.order_ttl')));
        return $order;
    }
    //众筹商品下单
    public function crowdfunding(User $user,UserAddress $userAddress,ProductSku $productSku,$amount)
    {
        //开启事物
       $order= DB::transaction(function ()use($user,$userAddress,$productSku,$amount){
            //更新地址的最后使用时间
            $userAddress->update(['last_used_at'=>Carbon::now()]);
            //创建一个订单
            $order=new Order([
               'address'=>[
                   'address'=>$userAddress->full_address,
                   'zip'=>$userAddress->zip,
                   'contact_name'=>$userAddress->contact_name,
                   'contact_phone'=>$userAddress->contact_phone,
               ],
                'remark'=>'',
                'total_amount'=>$productSku->price * $amount,
                'type'=>Order::TYPE_CROWDFUNDING,
            ]);
            //订单关联到当前用户
            $order->user()->associate($user);
            //写入到数据库
            $order->save();
            //创建一个新的订单项并与sku关联
            $item=$order->items()->make([
               'amount'=>$amount,
               'price'=>$productSku->price,
            ]);
            $item->product()->associate($productSku->product_id);
            $item->productSku()->associate($productSku);
            $item->save();
            //扣减对应的sku库存
            if($productSku->decreaseStock($amount)<0){
                throw new InvalidRequestException('该商品库存不足');
            }
            return $order;
        });
        //众筹结束时间送去当前时间得到的剩余秒数
        $crowdfundingTtl=$productSku->product->crowdfunding->end_at->getTimestamp()-time();
        //订单关闭任务,剩余秒数与默认订单关闭时间取较小的作为订单关闭时间
        dispatch(new CloseOrder($order, min($crowdfundingTtl,config('app.order_ttl'))));
        return $order;
    }
    //退款逻辑
    public function _refundOrder($order)
    {
        //1.先判断订单的支付方式,进行不同的退款操作,微信和支付宝
        switch ($order->payment_method){
            case 'wechat':
                //先留空
                $refund_no=Order::getAvailableRefundNo();
                //调用微信里的退款访求
                $refundData = [
                    'out_trade_no' => $order->no,
                    'out_refund_no' =>$refund_no,
                    'total_fee' => $order->total_amount*100,//微信是以分为单位的
                    'refund_fee' => $order->total_amount*100,
                    'refund_desc' => '退款',
                    'notify_url'=>ngrok_url('payment.wechat.refund_notify')
                ];
                $ret=app('wechat_pay')->refund($refundData);//// 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                //将订单状态改成退款中
                $order->update([
                    'refund_no'=>$refund_no,
                    'refund_status'=>Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                //获取唯一的订单号
                $refund_no=Order::getAvailableRefundNo();
                //调用支付宝里的退款方法
                $refundData=[
                    'out_trade_no'=>$order->no,
                    'refund_amount'=>$order->total_amount,//退款金额
                    'out_request_no'=>$refund_no,
                ];
                $ret=app('alipay')->refund($refundData);
                if($ret->sub_code){ //退款成功后,这个字段是没有值的
                    //退款失败后
                    $extra=$order->extra;
                    $extra['refund_fail_code']=$ret->sub_code;
                    //将订单的退款状态标记为退款失败
                    $order->update([
                        'refund_no'=>$refund_no,
                        'refund_status'=>Order::REFUND_STATUS_FAILED,
                        'extra'=>$extra
                    ]);
                }else{
                    //退款成功,修改退款状态,并保存退款订单号
                    $order->update([
                        'refund_no'=>$refund_no,
                        'refund_status'=>Order::REFUND_STATUS_SUCCESS,
                    ]);
                    
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no'=>Order::getAvailableRefundNo(),
                    'refund_status'=>Order::REFUND_STATUS_PROCESSING
                ]);
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw  new InternalException('未知订单支付方式:'.$order->payment_method);
                break;
        }
    }
    //秒杀
    public function seckill(User $user,UserAddress $address,ProductSku $sku)
    {
        $order=DB::transaction(function () use ($user, $address, $sku) {
            //更新些地址的最后使用时间
            $address->update(['last_used_at'=>Carbon::now()]);
            //创建一个订单
            $order=new Order([
                'address'=>[//将地址信息放入到订单中
                    'address'=>$address->full_address,
                    'zip'=>$address->zip,
                    'contact_name'=>$address->contact_name,
                    'contact_phone'=>$address->contact_phone,
                ],
                'remark'=>'',
                'total_amount'=>$sku->price,//秒杀只能选一件
                'type'=>Order::TYPE_SECKILL
            ]);
            //订单关联到当前用户
            $order->user()->associate($user);
            //写入到数据库
            $order->save();
            //创建一个新的订单与sku关联
            $item=$order->items()->make([
                'amount'=>1,
                'price'=>$sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();
            //扣减对应的sku库存
            if($sku->decreaseStock(1)<=0){
                throw new InvalidRequestException('该商品库存不足');
            }
            return $order;
        });//秒杀订单的自动关闭
        dispatch(new CloseOrder($order, config('app.seckill_order_ttl')));
        return $order;
    }
}