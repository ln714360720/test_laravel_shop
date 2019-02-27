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
}