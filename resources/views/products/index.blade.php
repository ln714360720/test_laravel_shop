@extends('layouts.app')
@section('title', '商品列表')

@section('content')
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="panel panel-default">
                <div class="panel-body">
                    <!-- 筛选组件开始 -->
                    <div class="row">
                        <form action="{{ route('products.index') }}" class="form-inline search-form">
                            <input type="hidden" name="filters">
                            <div class="form-row">
                                <div class="col-md-9">
                                    <div class="form-row">
                                        {{--面包屑--}}
                                        <div class="col-auto category-breadcrumb">
                                            {{--添加一个全部的a链接--}}
                                            <a href="{{route('products.index')}}" class="all-products">全部111</a>
                                            {{--如果当时的类目是通过筛选的--}}
                                            @if($category)
                                                {{--遍历这个类目的所有祖先类目,我们在模型的访问器中已经弄好,可以直接使用--}}
                                                @foreach($category->ancestors as $ancestor)
                                                    {{--添加一个名该祖先类目的链接--}}
                                                    <span class="category">
                                                        <a href="{{route('products.index',['category_id'=>$ancestor->id])}}">{{$ancestor->name}}</a><span>&gt;</span>
                                                    </span>
                                                @endforeach
                                                {{--最后展示当前类目名称--}}
                                                <span class="category">{{$category->name}}</span><span> ></span>
                                                {{--当前类目的id 当用户调整排序方式时,可以保证category_id参数不会丢失--}}
                                                @endif
                                            {{--商品属性面包屑开始--}}
                                            {{--遍历当前属性筛选条件--}}
                                            @foreach($propertyFilters as $name=>$value)
                                              <span class="filter">{{$name}}:
                                                <span class="filter-value">{{$value}}</span>
                                                {{--调用之后定义的removeFilterFromQuery--}}
                                                <a href="javascript:removeFilterFromQuery('{{$name}}')" class="remove-filter">x</a>
                                                </span>
                                            @endforeach
                                        </div>


                                    </div>

                                </div>
                                <input type="text" class="form-control input-sm" name="search" placeholder="搜索">
                                <button class="btn btn-primary btn-sm" style="margin-left: 20px">搜索</button>
                                <select name="order" class="form-control input-sm pull-right " style="margin-left: 50px" >
                                    <option value="">排序方式</option>
                                    <option value="price_asc">价格从低到高</option>
                                    <option value="price_desc">价格从高到低</option>
                                    <option value="sold_count_desc">销量从高到低</option>
                                    <option value="sold_count_asc">销量从低到高</option>
                                    <option value="rating_desc">评价从高到低</option>
                                    <option value="rating_asc">评价从低到高</option>
                                </select>
                            </div>

                        </form>
                    </div>
                    {{--展示子类目--}}
                    <div class="filters">
                        {{--如果当前是通过类目选择,并且些类目是一个父类目--}}
                        @if($category && $category->is_directory)
                            <div class="row">
                                <div class="col-3 filter-key">
                                    子类目:
                                </div>
                                <div class="col-9 filter-values">
                                    {{--遍历直接子类目--}}
                                @foreach($category->children as $child)
                                        <a href="{{route('products.index',['category_id'=>$child->id])}}">{{$child->name}}</a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        {{--分面搜索结果开始--}}
                        @foreach($properties as $property)
                            <div class="row">
                                {{--输出属性名--}}
                                <div class="col-3 filter-key">
                                    {{$property['key']}}
                                </div>
                                <div class="col-9 filter-values">
                                    {{--遍历属性值列表--}}
                                    @foreach($property['values'] as $value)
                                        <a href="javascript:appendFilterToQuery('{{$property['key']}}','{{$value}}');">{{$value}}</a>
                                        @endforeach
                                </div>
                            </div>
                            @endforeach
                        {{--分面搜索结果结束--}}
                    </div>
                    <div class="row products-list">
                        @foreach($products as $product)
                            <div class="col-xs-3 product-item">
                                <div class="product-content">
                                    <div class="top">
                                        <a href="{{route('products.show',array('product'=>$product->id))}}"><div class="img"><img src="{{ $product->image_url }}" alt=""></div></a>
                                        <div class="price"><b>￥</b>{{ $product->price }}</div>
                                        <div class="title"><a href="{{route('products.show',array('product'=>$product->id))}}">{{ $product->title }}</a></div>
                                    </div>
                                    <div class="bottom">
                                        <div class="sold_count">销量 <span>{{ $product->sold_count }}笔</span></div>
                                        <div class="review_count">评价 <span>{{ $product->review_count }}</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="pull-right">{{$products->appends($filters)->links()}}</div>
            </div>
        </div>
    </div>
@endsection
@section('scriptsAfterJs')
    <script type="text/javascript">
        var filters = {!! json_encode($filters) !!};
        // 属性值搜索使用
        //定义一个函数用于解析当前url里的参数,并以key-value对象形式返回
        function parseSearch(){

            //初始化一个对象
            var searches={};
            //location.search 分返回url中的?及后台的查询参数
            //substr()可以将?去除然后以符号&分割成数组,然后遍历这个数组
            location.search.substr(1).split('&').forEach(function (str) {
                //将字符串以符号=分割成数组
                var result=str.split('=');
                //将数组的第一个值解码之后作为key,第二个值解码后作为value,放到之前初始化的对象中
                searches[decodeURIComponent(result[0])]=decodeURIComponent(result[1]);
            })

            return searches;
        }
        //根据key-value对象构建查询参数
        function buildSearch(searches){
            //初始化字符串
            var query='?';
            //遍历searches对象
            _.forEach(searches,function (value,key) {
                query +=encodeURIComponent(key)+'='+encodeURIComponent(value)+'&';
            });
            //去除最后一个&符
            return query.substr(0,query.length-1);
        }
        //将新的filter追加到当前的url里
        function appendFilterToQuery(name,value){
            //解析当前url的查询参数
            var searches=parseSearch();
            //如果已经有了filters查询
            if(searches['filters']){
                //则在filters后追加
                searches['filters'] +='|'+name+':'+value;
            }else{
                //否则初始化filters
                searches['filters']=name+':'+value;
            }
            //重新构建查询参数,并触发浏览器跳转
            location.search=buildSearch(searches);
        }
        //将某个属性从filters里排除
        function removeFilterFromQuery(name){

            //解析当前url的查询参数
            var searches=parseSearch();
            //如果没有filters查询则什么也不做
            if(!searches['filters']){
                return;
            }
            //初始化一个空数组
            var filters=[];
            //将filters字符串拆解
            searches['filters'].split('|').forEach(function (filter) {
                //解析出属性名和属性值
                var result=filter.split(':');

                //如果当前的属性名与要移除的属性名一致,则退出
                if(result[0]===name){
                    return;
                }
                //否则将这个filter放入到之前初始化的数组中
                filters.push(filter);
            });
            searches['filters']=filters.join('|');
            //重新构建查询,并触发浏览器跳转
            location.search=buildSearch(searches);
        }
        $(function () {
            $('input[name=search]').val(filters.search);
            $('select[name=order]').val(filters.order);
            //选择后自动提交
            $('select[name=order]').on('change',function () {
                //解析当前查询参数
                var searches=parseSearch();
                //如果有属性筛选
                if(searches['filters']){
                    //将属性筛选值放入隐藏字段中
                    $('.search-form input[name=filters]').val(searches['filters']);
                }
                $('.search-form').submit();
            })
        })
    </script>
    @endsection