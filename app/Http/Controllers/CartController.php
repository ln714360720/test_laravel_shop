<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;
    /**
     * CartController constructor.
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService=$cartService;
    }
    
    public function add(AddCartRequest $addCartRequest)
    {
        
        $skuId=$addCartRequest->input('sku_id');
        $amount=$addCartRequest->input('amount');
        $this->cartService->add($skuId, $amount);
        return [];
    }
    //显示购物车
    public function index(Request $request)
    {
        //获取当前用户的收货地址
        $addresses=$request->user()->addresses()->orderBy('last_used_at','desc')->get();
        $cartItems=$this->cartService->get();
        return view('cart.index',['cartItems'=>$cartItems,'addresses'=>$addresses]);
    }
    //删除购物车信息
    public function remove(ProductSku $sku,Request $request)
    {
        $this->cartService->remove($sku->id);
        return [];
    }
}
