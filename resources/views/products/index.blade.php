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
                            <div class="form-row">
                                <div class="col-md-9">
                                    <div class="form-row">
                                        {{--面包屑--}}
                                        <div class="col-auto category-breadcrumb">
                                            {{--添加一个全部的a链接--}}
                                            <a href="{{route('products.index')}}" class="all-products">全部</a>
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

        $(function () {
            $('input[name=search]').val(filters.search);
            $('select[name=order]').val(filters.order);
            //选择后自动提交
            $('select[name=order]').on('change',function () {
                $('.search-form').submit();
            })
        })
    </script>
    @endsection