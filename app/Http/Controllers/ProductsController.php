<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductsController extends Controller
{
    //
    public function index(Request $request)
    {
       
        $page=$request->input('page',1);
        $perPage=16;
        //构建查询
        $params=[
            'index'=>'products',
            'type'=>'_doc',
            'body'=>[
                'from'=>($page-1)*$perPage,//通过当前页数与每页数量计算偏移值
                'size'=>$perPage,
                
                'query'=>[
                    'bool'=>[
                        'filter'=>[
                            ['term'=>['on_sale'=>true]],
                        ],
                    ]
                ]
            ]
        ];
      
        //是否有提交order参数 如果有则赋值给order变量
        if($order=$request->input('order','')){
            //是否已asc或者是desc结束
            if(preg_match('/^(.+)_(asc|desc)/', $order,$m)){
                //如果字体串的开头是这三个里面的一个就说明是合法的
                if(in_array($m[1], ['price','sold_count','rating'])){
                    //根据传入的排序值来构造排序参数
                    $params['body']['sort']=[$m[1]=>$m[2]];
                }
            }
        }
        //类目筛选
        if($request->input('category_id')&&$category=Category::query()->find($request->input('category_id'))){
        
            if($category->is_directory){
                //如果是一个父类目,则使用Category_path来筛选
                $params['body']['query']['bool']['filter'][]=[
                    'prefix'=>['category_path'=>$category->path.$category->id.'-'],
                ];
            }else{
            
                //否则直接通过category_id筛选
                $params['body']['query']['bool']['filter'][]=[
                    'term' =>['category_id'=>$category->id],
                ];
            }
        }
        //关键词搜索
        if($search=$request->input('search','')){
            /**这是关键字搜索,分为两种情况, 一种是用户输入的值,中间没有空格的时候,如 '内存条金士顿',还有一种情况是用户输入的关键字中间有空格
             * 下面的是第一种没有空格的,先使用分词器,获取分词后的结果,暂时同义词搜索还没有实现如 搜 苹果手机时,分出现iphone的商品
             */
       
//            $param=[
//                'index'=>'products',
//                'body'=>[
//                    'text'=>$search,
//                    'analyzer'=>'ik_smart',
//
//                ]
//            ];
//            $res=app('es')->indices()->analyze($param);//获取分词后的数据
////
//////////            //通过collect 提取token字段
//            $keywords=collect($res['tokens'])->pluck('token')->all();
////            //遍历搜索数组,分别添加到must查询中
////
//            foreach ($keywords as $keyword) {
//                $params['body']['query']['bool']['must'][]=[
//                    'multi_match'=>[
//                        'query'=>$keyword,
//                        'fields'=>[
//                            'title^2','long_title^2','category^2','description',
//                            'skus_title','skus_description','properties_value',
//                        ],
//                        'analyzer'=>'ik_smart_synonym'
//                    ],
//                ];
//            }
            /**
             * 没有空格的结束
             */
//            =========
            /**
             * 这是用户输入的关键字中间有空格时,这个是有同义词的匹配的
             */
          $keywords=array_filter(explode(' ', $search));
            $params['body']['query']['bool']['must']=[];
            //遍历搜索数组,分别添加到must查询中
            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][]=[
                    'multi_match'=>[
                        'query'=>$keyword,
                        'fields'=>[
                            'title^2','long_title^2','category^2','description',
                            'skus_title','skus_description','properties_value',
                        ],

                    ],
                ];
            }
            /**
             * 有空格的结束
             */
//            =======
            //只有当是搜索时或者是点击了分类时,才会触发聚合操作
            if($search || isset($category)) {
                $params['body']['aggs'] = [
                    // 这里的 properties 是我们给这个聚合操作的命名
                    // 可以是其他字符串，与商品结构里的 properties 没有必然联系
                    'properties' => [
                        // 由于我们要聚合的属性是在 nested 类型字段下的属性，需要在外面套一层 nested 聚合查询
                        'nested' => [
                            // 代表我们要查询的 nested 字段名为 properties
                            'path' => 'properties',
                        ],
                        // 在 nested 聚合下嵌套聚合
                        'aggs' => [
                            // 聚合的名称
                            'properties' => [
                                // terms 聚合，用于聚合相同的值
                                'terms' => [
                                    'field' => 'properties.name',
                                ],
                                //第三层聚合,在name上基础上聚合
                                'aggs' => [
                                    //聚合的名称
                                    'value' => [
                                        'terms' => [
                                            'field' => 'properties.value',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }

        }
        //从用户请求参数里获取filters
        $propertyFilters=[];
        if($filterString=$request->input('filters')){
            //将获取到的字符串用符号|拆分成数组
            $filterArray=explode('|', $filterString);
            foreach ($filterArray as $filter) {
                //将字符串用户符号:拆分成两个部分,并且分别赋值给$name,$value
                list($name,$value)=explode(':', $filter);
                //将用户筛选的属性添加到数组中
                $propertyFilters[$name]=$value;
                //添加到filter类型中
                $params['body']['query']['bool']['filter'][]=[
//                  由于我们是筛选的nested类型的属性,因此需要用nested查询
                    'nested'=>[
                        //指明nested字段
                        'path'=>'properties',
                        'query'=>[
                            ['term'=>['properties.search_value'=>$filter]],
                            
                            ]
                        ]
                    
                ];
            }
        }
       
        $result=app('es')->search($params);
        //处理分面搜索
        $properties=[];
        //如果返回结果里有aggregations字段,说明做了分面聚合操作
        if(isset($result['aggregations'])){
//            使用collect函数将返回值转为集合
            $properties=collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function($bucket){
                   //通过map方法取出我们需要的字段
                    return [
                      'key'=>$bucket['key'],
                      'values'=>collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })->filter(function($property) use ($propertyFilters){
                    //过滤掉只剩下一个值或者已经在筛选条件里的属性
                    return count($property['values'])>1 && !isset($propertyFilters[$property['key']]);
                });
            
        }
       //通过collect 函数将返回的结果转化为对象,并通过pluck访求取到id
        $productIds=collect($result['hits']['hits'])->pluck('_id')->all();
        //通过whereIn方法从数据库中读取商品数据
        $products=Product::query()->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id,'%s')",join(',', $productIds)))
            ->get();
        //返回一个lengthAwarepaginator对象
        $pager=new LengthAwarePaginator($products, $result['hits']['total'], $perPage,$page,['path'=>route('products.index',false)]);
        
        return view('products.index',[
            'products'=>$pager,
            'filters'=>['search'=>$search,'order'=>$order],
            'category'=>$category??null,
            'properties'=>$properties,
            'propertyFilters'=>$propertyFilters,
        ]);
        ////分割线==========以下部分是没有使用Elasticsearch的代码======
//        $builder=Product::query()->where('on_sale',true);
//        if($search=$request->input('search','')){
//            $like='%'.$search.'%';
//            //模糊搜索商品标题,商品详情,sku标题,sku描述
//            $builder->where(function ($query) use ($like) {
//                    $query->where('title','like',$like)
//                        ->orWhere('description','like',$like)
//                        ->orWhereHas('skus',function ($query) use ($like){
//                            $query->where('title','like',$like)
//                                ->orWhere('description','like',$like);
//                        });
//            });
////            $like = '%'.$search.'%';
////            $builder->where('title', 'like', $like)
////                ->orWhere('description', 'like', $like)
////                ->orWhereHas('skus', function ($query) use ($like) {
////                    $query->where('title', 'like', $like)
////                        ->orWhere('description', 'like', $like);
////                });
//        }
//        //如果传入category_id字段,并在数据库中有对应的类目
//        if($request->input('category_id')&& $category=Category::query()->find($request->input('category_id'))){
//            //如果这是一个父类目
//            if($category->is_directory){
//                //则筛选出父类目下的所有子类目的商品
//                $builder->whereHas('category', function ($query) use ($category) {
//                $query->where('path','like',$category->path.$category->id.'-%');
//                });
//            }else {
//                //如果这不是一个父类目,则直接筛选此类目的商品
//                $builder->where('category_id',$category->id);
//            }
//        }
//        //判断是否有order参数
//        if($order=$request->input('order','')){
//            //是否以_asc 或者是 _desc结尾的的
//            if(preg_match('/^(.+)_(asc|desc)$/', $order,$m)){
//                if(in_array($m[1], ['price','sold_count','rating'])){
//                    $builder->orderBy($m[1],$m[2]);
//                }
//            }
//        }
//        $products=$builder->paginate(16);
////        dd(DB::getQueryLog());
//        return view('products.index',['products'=>$products,
//            'category'=>$category??null,'filters'=>['search'=>$search,'order'=>$order]]);
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