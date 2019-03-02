<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\CrowdFundingOrderRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\Request;
use App\Http\Requests\SendReviewRequest;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function store(OrderRequest $request,OrderService $orderService)
    {
        $user=$request->user();
       $address=UserAddress::find($request->input('address_id'));
       $remark= $request->input('remark');
       $items= $request->input('items');
       $coupon=null;
       $coupon_code=$request->input('coupon_code');
       if($coupon_code){
           $coupon=CouponCode::query()->where('code',$coupon_code)->first();
           if(!$coupon){
               throw new CouponCodeUnavailableException('优惠券不存在');
           }
       }
       
       return $orderService->store($user, $address, $remark, $items,$coupon);
    }
    
    public function index(Request $request)
    {
        //使用with方法预加载 避免n+1问题
        $orders=Order::query()->with(['items.product','items.productSku'])
            ->where('user_id',$request->user()->id)
            ->orderBy('created_at','desc')
            ->paginate(16);
        return view('orders.index',compact('orders'));
    }
    
    /**
     * 这里的 load() 方法与上一章节介绍的 with() 预加载方法有些类似，称为 延迟预加载，不同点在于 load() 是在已经查询出来的模型上调用，而 with() 则是在 ORM 查询构造器上调用。
     * @param Order   $order
     * @param Request $request
     */
    public function show(Order $order,Request $request)
    {
        try{
            $this->authorize('own',$order);
        }catch (\Exception $e){
            throw  new InvalidRequestException('不可能让你看!');
        }
       
        $order=$order->load(['items.productSku','items.product']);
        return view('orders.show',compact('order'));
//        dd($order->load(['items.productSku','items.product']));
    }
    
    /**用户收货
     * @param Order   $order
     * @param Request $request
     */
    public function received(Order $order,Request $request)
    {
        $this->authorize('own',$order);
        //判断订单的发货状态是否为已发货状态
        if($order->ship_status !==Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('发货状态不正确!');
        }
        //更新发货状态为已收货
        $order->update([
            'ship_status'=>Order::SHIP_STATUS_RECEIVED
        ]);
        return $order;
    }
    
    public function review(Order $order)
    {
        //判断订单权限
        $this->authorize('own',$order);
        //判断订单是否已支付
        if(!$order->paid_at){
            throw new InvalidRequestException('订单还没有支付!');
        }
        return view('orders.review',['order'=>$order->load(['items.product','items.productSku'])]);
    }
    
    public function sendReview(SendReviewRequest $request,Order $order)
    {
        //权限检查
        $this->authorize('own',$order);
        //判断订单是否已支付
        if(!$order->paid_at){
            throw new InvalidRequestException('订单还没有支付!');
        }
        //判断订单是否已评价
        if($order->reviewed){
            throw new InvalidRequestException('该订单已评价');
        }
        $reviews=$request->input('reviews');
        //开启事务
        DB::transaction(function ()use ($reviews,$order){
            //遍历用户提交的数据
            foreach ($reviews as $review){
                $orderItem=$order->items()->find($review['id']);
                //保存评分和评价
                $orderItem->update([
                    'rating'=>$review['rating'],
                    'review'=>$review['review'],
                    'review_at'=>Carbon::now(),
                ]);
            }
            //修改订单为已评论
            $order->update([
               'reviewed'=>true,
            ]);
            event(new OrderReviewed($order));
        });
        return redirect()->back();
    }
    
    public function applyRefund(Order $order,Request $request)
    {
        //验证当前用户权限
        $this->authorize('own',$order);
        //验证是否输入退款理由
        $this->validate($request, ['reason'=>'required'],[],['reason'=>'退款原因']);
       
        //判断订单是否已付款
        if(!$order->paid_at){
            throw new InvalidRequestException('该订单未支付,不可退款');
        }
        //众筹订单不允许申请退款
        if($order->type ===Order::TYPE_CROWDFUNDING){
            throw new InvalidRequestException('众筹订单不支持退款');
        }
        //判断当前订单是否已经提交退款申请
        if($order->refund_status !== Order::REFUND_STATUS_PENDING){
            throw new InvalidRequestException('该订单已经申请退款,请忽重申请');
        }
        //将用户输入的退款理由放到订单的extra字段中
        $extra=$order->extra?:[];
        $extra['refund_reason']=$request->input('reason');
        //将订单退款状态改为已申请退款
        $order->update([
            'refund_status'=>Order::REFUND_STATUS_APPLIED,
            'extra'=>$extra,
        ]);
        return $order;
    }
    
    /**众筹商品下单处理
     * @param CrowdFundingOrderRequest $request 过滤请求类
     * @param OrderService             $orderService
     * @return mixed
     */
    public function crowdfunding(CrowdFundingOrderRequest $request,OrderService $orderService)
    {
        $user=$request->user();
        $sku=ProductSku::query()->find($request->input('sku_id'));
        $address=UserAddress::query()->find($request->input('address_id'));
        $amount=$request->input('amount');
        return $orderService->crowdfunding($user, $address, $sku, $amount);
    }
}
