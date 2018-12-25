<?php
/**
 * Created by PhpStorm.
 * User: xs
 * Date: 2018/12/25
 * Time: 15:26
 */

namespace App\Services;


use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function store(User $user,UserAddress $userAddress,$remark,$items)
    {
        //开一个事务
        $order=DB::transaction(function () use ($user,$userAddress,$remark,$items){
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
}