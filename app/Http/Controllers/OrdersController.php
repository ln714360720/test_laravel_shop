<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\Request;
use App\Jobs\CloseOrder;
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
       return $orderService->store($user, $address, $remark, $items);
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
}
