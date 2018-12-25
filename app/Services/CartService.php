<?php
namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartService{
    /** 获取购物车信息
     * @return mixed
     */
    public function get()
    {
        return Auth::user()->cartItems()->with(['productSku.product'])->get();
    }
    
    /** 添加购物车
     * @param $skuId skuid
     * @param $amount 商品数量
     */
    public function add($skuId,$amount)
    {
        $user=Auth::user();
        //数据库中查询该商品是否已经在购物车中
        if($item=$user->cartItems()->where('product_sku_id',$skuId)->first()){
            $item->update([
                'amount' => $item->amount + $amount,
            ]);
        }else{
            $item= new CartItem(['amount'=>$amount]);
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();
        }
        return $item;
    }
    
    /** 删除购物车商品
     * @param $skuIds
     */
    public function remove($skuIds){
        //可以传一个id,也可以是一个id 数组
        if(!is_array($skuIds)){
            $skuIds=[$skuIds];
        }
       return Auth::user()->cartItems()->whereIn('product_sku_id',$skuIds)->delete();
    }
}