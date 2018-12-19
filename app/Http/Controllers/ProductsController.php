<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductsController extends Controller
{
    //
    public function index(Request $request)
    {
//        DB::enableQueryLog();
        $builder=Product::query()->where('on_sale',true);
        if($search=$request->input('search','')){
            $like='%'.$search.'%';
            //模糊搜索商品标题,商品详情,sku标题,sku描述
            $builder->where(function ($query) use ($like) {
                    $query->where('title','like',$like)
                        ->orWhere('description','like',$like)
                        ->orWhereHas('skus',function ($query) use ($like){
                            $query->where('title','like',$like)
                                ->orWhere('description','like',$like);
                        });
            });
//            $like = '%'.$search.'%';
//            $builder->where('title', 'like', $like)
//                ->orWhere('description', 'like', $like)
//                ->orWhereHas('skus', function ($query) use ($like) {
//                    $query->where('title', 'like', $like)
//                        ->orWhere('description', 'like', $like);
//                });
        }
        //判断是否有order参数
        if($order=$request->input('order','')){
            //是否以_asc 或者是 _desc结尾的的
            if(preg_match('/^(.+)_(asc|desc)$/', $order,$m)){
                if(in_array($m[1], ['price','sold_count','rating'])){
                    $builder->orderBy($m[1],$m[2]);
                }
            }
        }
        $products=$builder->paginate(16);
//        dd(DB::getQueryLog());
        return view('products.index',['products'=>$products,'filters'=>['search'=>$search,'order'=>$order]]);
    }
    
    /**显示商品详情
     * @param Request $request
     * @param Product $product 商品模型
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws InvalidRequestException
     */
    public function show(Request $request,Product $product)
    {
        //判断是否上架,如果没有上架,则抛出异常
        if(!$product->on_sale){
            throw new InvalidRequestException('商品未上架');
        }
        $favorite=false;
        if($user=$request->user()){
            $favorite=boolval($user->favoriteProducts()->find($product->id));
        }
        return view('products.show',['product'=>$product,'favorite'=>$favorite]);
    }
    
    /**用户添加收藏
     * @param Product $product
     * @param Request $request
     * @return array
     */
    public function favor(Product $product,Request $request)
    {
        $user=$request->user();
        if($user->favoriteProducts()->find($product->id)){
            return [];
        }
        $user->favoriteProducts()->attach($product);
        return [];
    }
    
    public function disfavor(Product $product,Request $request)
    {
        
        $user=$request->user();
        $res=$user->favoriteProducts()->detach($product);
        dd($res);
//        return [];
    }
    
    public function favorites(Product $product,Request $request)
    {
        $products=$request->user()->favoriteProducts()->paginate(16);
        return view('products.favorites',['products'=>$products]);
    }
}