<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(AddCartRequest $addCartRequest)
    {
        $user=$addCartRequest->user();
        $skuId=$addCartRequest->input('sku_id');
        $amount=$addCartRequest->input('amount');
        //从数据库中查询该商品是否已经在购物车中,如果在,则修改数量,如果没有,则添加一条数据
        if($cart=$user->cartItems()->where('product_sku_id',$skuId)->first()){
           
            $cart->update(['amount'=>$amount+$cart->amount]);
        }else{
            $cart=new CartItem(['amount'=>$amount]);
            $cart->user()->associate($user);
            $cart->productSku()->associate($skuId);
            $cart->save();
        }
        return [];
    }
    //显示购物车
    public function index(Request $request)
    {
        $cartItems=$request->user()->cartItems()->with(['productSku.product'])->get();
        return view('cart.index',['cartItems'=>$cartItems]);
    }
    //删除购物车信息
    public function remove(ProductSku $sku,Request $request)
    {
        
        $request->user()->cartItems()->where('product_sku_id',$sku->id)->delete();
        return [];
    }
}
