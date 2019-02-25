<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
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
        //如果传入category_id字段,并在数据库中有对应的类目
        if($request->input('category_id')&& $category=Category::query()->find($request->input('category_id'))){
            //如果这是一个父类目
            if($category->is_directory){
                //则筛选出父类目下的所有子类目的商品
                $builder->whereHas('category', function ($query) use ($category) {
                $query->where('path','like',$category->path.$category->id.'-%');
                });
            }else {
                //如果这不是一个父类目,则直接筛选此类目的商品
                $builder->where('category_id',$category->id);
            }
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
        return view('products.index',['products'=>$products,'category'=>$category??null,'filters'=>['search'=>$search,'order'=>$order]]);
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
        $reviews=OrderItem::query()->with(['productSku','order.user'])
            ->where('product_id',$product->id)
            ->whereNotNull('review_at')->orderBy('review_at','desc')
            ->limit(10)->get();
        return view('products.show',['product'=>$product,'favorite'=>$favorite,'reviews'=>$reviews]);
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