@extends('layouts.app')
@section('title', $product->title)

@section('content')
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="panel panel-default">
                <div class="panel-body product-info">
                    <div class="row">
                        <div class="col-sm-5">
                            <img class="cover" src="{{ $product->image_url }}" alt="">
                        </div>
                        <div class="col-sm-7">
                            <div class="title">{{$product->long_title?: $product->title }}</div>
                            {{--众筹模块开始--}}
                            @if($product->type==\App\Models\Product::TYPE_CROWDFUNDING)
                                <div class="crowdfunding-info">
                                    <div class="have-text">已筹到</div>
                                    <div class="total-amount"><span class="symbol">￥</span>{{ $product->crowdfunding->total_amount }}</div>
                                    <!-- 这里使用了 Bootstrap 的进度条组件 -->
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-success progress-bar-striped"
                                             role="progressbar"
                                             aria-valuenow="{{ $product->crowdfunding->percent }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100"
                                             style="min-width: 1em; width: {{ min($product->crowdfunding->percent, 100) }}%">
                                        </div>
                                    </div>
                                    <div class="progress-info">
                                        <span class="current-progress">当前进度：{{ $product->crowdfunding->percent }}%</span>
                                        <span class="float-right user-count">{{ $product->crowdfunding->user_count }}名支持者</span>
                                    </div>
                                    <!-- 如果众筹状态是众筹中，则输出提示语 -->
                                    @if ($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                        <div>此项目必须在
                                            <span class="text-red">{{ $product->crowdfunding->end_at->format('Y-m-d H:i:s') }}</span>
                                            前得到
                                            <span class="text-red">￥{{ $product->crowdfunding->target_amount }}</span>
                                            的支持才可成功，
                                            <!-- Carbon 对象的 diffForHumans() 方法可以计算出与当前时间的相对时间，更人性化 -->
                                            筹款将在<span class="text-red">{{ $product->crowdfunding->end_at->diffForHumans(now()) }}</span>结束！
                                        </div>
                                    @endif
                                </div>
                               @else
                                <div class="price"><label>价格</label><em>￥</em><span>{{ $product->price }}</span></div>
                                <div class="sales_and_reviews">
                                    <div class="sold_count">累计销量 <span class="count">{{ $product->sold_count }}</span></div>
                                    <div class="review_count">累计评价 <span class="count">{{ $product->review_count }}</span></div>
                                    <div class="rating" title="评分 {{ $product->rating }}">评分 <span class="count">{{ str_repeat('★', floor($product->rating)) }}{{ str_repeat('☆', 5 - floor($product->rating)) }}</span></div>
                                </div>
                                @endif

                            <div class="skus">
                                <label>选择</label>
                                <div class="btn-group" data-toggle="buttons">
                                    @foreach($product->skus as $sku)
                                        <label class="btn btn-default sku-btn" data-price="{{ $sku->price }}"  data-stock="{{ $sku->stock }}" data-toggle="tooltip"   title="{{ $sku->description }}"  data-placement="bottom">
                                            <input type="radio" name="skus" autocomplete="off" value="{{ $sku->id }}"> {{ $sku->title }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="cart_amount"><label>数量</label><input type="text" class="form-control input-sm" value="1"><span>件</span><span class="stock"></span></div>
                            <div class="buttons">
                                @if($favorite)
                                    <button class="btn btn-success btn-disfavor">取消收藏</button>
                                @else
                                    <button class="btn btn-success btn-favor">❤ 收藏</button>
                                @endif
                                {{--众筹下单按钮开始--}}

                                    @if($product->type===\App\Models\Product::TYPE_CROWDFUNDING)
                                        @if(Auth::check())
                                            @if($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                                <button class="btn btn-primary btn-crowdfunding">参与众筹</button>
                                                @else
                                                <button class="btn btn-primary disabled">
                                                    {{\App\Models\CrowdfundingProduct::$statusMap[$product->crowdfunding->status]}}
                                                </button>
                                            @endif
                                            @else
                                            <a href="{{route('login')}}" class="btn btn-primary">请先登录</a>
                                            @endif
                                            {{--秒杀商品下单按钮开始--}}
                                        @elseif($product->type ===\App\Models\Product::TYPE_SECKILL)
                                        @if(Auth::check())
                                                @if($product->seckill->is_before_start)
                                                    <button class="btn btn-primary btn-seckill disabled countdown">抢购倒计时</button>
                                                    @elseif($product->seckill->is_after_end)
                                                    <button class="btn btn-primary btn-seckill disabled">抢购已结束</button>
                                                    @else
                                                        <button class="btn btn-primary btn-seckill">立即抢购</button>
                                                    @endif
                                            @else
                                            <a href="{{route('login')}}" class="btn btn-primary">请先登录</a>
                                            @endif
                                        @else
                                        <button class="btn btn-primary btn-add-to-cart">加入购物车</button>
                                        @endif

                            </div>
                        </div>
                    </div>
                    <div class="product-detail">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#product-detail-tab" aria-controls="product-detail-tab" role="tab" data-toggle="tab">商品详情</a></li>
                            &nbsp;
                            <li role="presentation"><a href="#product-reviews-tab" aria-controls="product-reviews-tab" role="tab" data-toggle="tab">用户评价</a></li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="product-detail-tab">
                                {{--产品属性开始--}}
                                <div class="properties-list">
                                    <div class="properties-list-title">产品参数：</div>
                                    <ul class="properties-list-body">
                                        @foreach($product->grouped_properties as $name=>$value)
                                            <li>{{ $name }}：{{ join(' ',$value) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="product-description">

                                    {!! $product->description !!}
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="product-reviews-tab">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <td>用户</td>
                                        <td>商品</td>
                                        <td>评分</td>
                                        <td>评价</td>
                                        <td>时间</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($reviews as $review)
                                        <tr>
                                            <td>{{ $review->order->user->name }}</td>
                                            <td>{{ $review->productSku->title }}</td>
                                            <td>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                                            <td>{{ $review->review }}</td>
                                            <td>{{ $review->review_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {{--猜你喜欢--}}
                    @if(count($similar) > 0)
                        <div class="similar-products">
                            <div class="title">猜你喜欢</div>
                            <div class="row products-list">
                                <!-- 这里不能使用 $product 作为 foreach 出来的变量，否则会覆盖掉当前页面的 $product 变量 -->
                                @foreach($similar as $p)
                                    <div class="col-3 product-item">
                                        <div class="product-content">
                                            <div class="top">
                                                <div class="img">
                                                    <a href="{{ route('products.show', ['product' => $p->id]) }}">
                                                        <img src="{{ $p->image_url }}" alt="">
                                                    </a>
                                                </div>
                                                <div class="price"><b>￥</b>{{ $p->price }}</div>
                                                <div class="title">
                                                    <a href="{{ route('products.show', ['product' => $p->id]) }}">{{ $p->title }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                @endif
                <!-- 猜你喜欢结束 -->
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scriptsAfterJs')
    {{--如果是秒杀商品并且还没有开始秒杀,则引入moment.js类库--}}
    @if($product->type==\App\Models\Product::TYPE_SECKILL && $product->seckill->is_before_start)
        <script src="https://cdn.bootcss.com/moment.js/2.24.0/moment.min.js"></script>
        @endif
    <script type="text/javascript">
        $(function () {
            //如果是秒杀商品并且还没有开始

                    @if($product->type==\App\Models\Product::TYPE_SECKILL && $product->seckill->is_before_start)
            //将秒杀开始时间转成一个moment对象
                    var startTime=moment.unix({{$product->seckill->start_at->getTimestamp()}});
                    console.log(startTime);
                    //设定一个定时器
                    var hd1=setInterval(function () {
                        //获取当前时间
                        var now=moment();
                        //如果当前时间晚于秒杀开始时间
                        if(now.isAfter(startTime)){
                            //将秒杀按钮上的disabled类移除,修改按钮文件
                            $('.btn-seckill').removeClass('disabled').removeClass('countdown').text('立即抢购');
                            clearInterval(hd1);
                            return;
                        }
                        //获取当前时间与秒杀相差的小时,分钟,秒数
                        var dayDiff=startTime.diff(now,'days');
                        var hourDiff=startTime.diff(now,'hours')%24;
                        var minDiff=startTime.diff(now,'minutes')%60;
                        var secDiff=startTime.diff(now ,'seconds')%60;
                            if(secDiff<10){
                              var  secDiff= '0'+secDiff;
                            }

                        if(0<dayDiff && dayDiff<10){
                            var  dayDiff= '0'+dayDiff;
                        }
                        if(hourDiff<10){
                            var  hourDiff= '0'+hourDiff;
                        }
                        if(minDiff<10){
                            var  minDiff= '0'+minDiff;
                        }
                        //修改按钮的文件
                        $('.btn-seckill').text('抢购倒计时 '+dayDiff+' 天:'+hourDiff+' 小时:'+minDiff+' 分钟:'+secDiff+' 秒')
                    },500);
                    @endif
                    //秒杀按钮点击事件
                $('.btn-seckill').click(function () {
                    //如果秒杀按钮上的disabled
                    if($(this).hasClass('disabled')){
                        return;
                    }
                    if(!$('label.active input[name=skus]').val()){
                        swal('请先选择商品');
                        return;
                    }
                    //把用户的收货地址以json的形式放入页面
                    var addresses={!! json_encode(Auth::check() ? Auth::user()->addresses : []) !!};
                   //使用jquery动态创建一个下拉框
                    var addressSelector=$('<select class="form-control"></select>');
                    // 循环每个收货地址
                    addresses.forEach(function (address) {
                        //把当前收货地址添加到地址下拉框选项中
                        addressSelector.append('<option value="'+address.id+'">'+address.full_address+' '+address.contact_name+' '+address.contact_phone+'</option>');
                    });
                    //调用swalAler弹框
                    swal({
                        'text':"选择收货地址",
                        content:addressSelector[0],
                        buttons:['取消','确定']
                    }).then(function(ret){
                        //如果用户没有点击确定按钮,则什么也不做
                        if(!ret){
                            return ;
                        }
                        //构建请求参数
                        var req={
                            address_id:addressSelector.val(),
                            sku_id:$("label.active input[name=skus]").val()
                        };
                        //调用秒杀商品接口
                        axios.post('{{route('secking_orders.store')}}',req).then(function (response) {
                            swal('订单提交成功','','success').then(()=>{
                               location.href='/orders/'+response.data.id;
                            });
                        },function(error){
                            // 输入参数校验失败，展示失败原因
                            if (error.response.status === 422) {
                                var html = '<div>';
                                _.each(error.response.data.errors, function (errors) {
                                    _.each(errors, function (error) {
                                        html += error+'<br>';
                                    })
                                });
                                html += '</div>';
                                swal({content: $(html)[0], icon: 'error'})
                            } else if (error.response.status === 403) {
                                swal(error.response.data.msg, '', 'error');
                            } else {
                                swal('系统错误', '', 'error');
                            }
                        })
                    })
                })
        })
        $(function () {
            $('[data-toggle="tooltip"]').tooltip({trigger:'hover'});
            $('.product-info .stock').text('库存' + $('.sku-btn').data('stock') + '件');
            $('.product-info .price span').text($('.sku-btn').data('price'));
            $(".sku-btn").click(function () {
                $('.product-info .price span').text($(this).data('price'));
                $('.product-info .stock').text('库存：' + $(this).data('stock') + '件');
            })
            //参与众筹 按钮点击事件
            $(".btn-crowdfunding").click(function () {
                //先判断是否选中sku
                if(!$('label.active input[name=skus]').val()){
                    swal('请先选择商品')
                    return;
                }
                //把用户的收货地址以json的形式入入页面,赋值给address变量
                var address={!! json_encode(Auth::check()?Auth::user()->addresses:[]) !!}
                if(!address){
                    swal('没有相关收货地址信息').then(function () {
                        location.href="{{route('user_addresses.create')}}";
                    })
                    return;
                }
                //动态创建一个表单
                var $form=$('<form></form>');
                //表单里添加一个收货地址的下拉框
                var select='<div class="form-group row">' +
                    '<label class="col-form-label col-sm-3">选择地址</label>' +
                    '<div class="col-sm-9">' +
                    '<select class="custom-select" name="address_id"></select>' +
                    '</div></div>';
                $form.append(select);
                //循环每个收货地址
                address.forEach(function (address) {
                    //把当前的收货地址添加到收货地址下拉框中
                    var opt="<option value='"+address.id+"'>"+
                            address.full_address+ ' '+
                            address.contact_name+' '+address.contact_phone+'</option>';
                    $form.find('select[name=address_id]')
                        .append(opt)
                });
                //在表单里添加一个名为购买数量的字段
                $form.append('<div class="form-group row">' +
                    '<label class="col-form-label col-sm-3">购买数量</label>' +
                    '<div class="col-sm-9"><input class="form-control" name="amount">' +
                    '</div></div>');
                //调用sweetalert弹框
                swal({
                    text:'参与众筹',
                    content:$form[0],
                    buttons:['取消','确定']
                }).then(function (ret) {
                    //如果用户没有点击确定按钮,则什么也不做
                    if(!ret){
                        return;
                    }
                    //构建请求参数
                    var req={
                        address_id:$form.find('select[name=address_id]').val(),
                        amount:$form.find('input[name=amount]').val(),
                        sku_id: $('label.active input[name=skus]').val()
                    };
                    //调用众筹商品下单接口
                    axios.post('{{route('crowdfunding_orders.store')}}',req)
                        .then(function (response) {
                        //订单创建成功,跳转到订单详情页
                            swal('订单提交成功','','success').then(function () {
                                location.href='/orders/'+response.data.id;
                            })
                    },function (error) {
                            //输入参数校验失败,展示失败原因
                            if(error.response.status==422){
                                var html = '<div>';
                                _.each(error.response.data.errors, function (errors) {
                                    _.each(errors, function (error) {
                                        html += error+'<br>';
                                    })
                                });
                                html += '</div>';
                                swal({content: $(html)[0], icon: 'error'})
                            }else if(error.response.status ==403){
                                swal(error.response.data.msg,'','error')
                            }else{
                                swal('系统错误','','error');
                            }
                        })
                })
            })

        })
        //收藏
        $('.btn-favor').click(function () {
            axios.post('{{route('products.favor',['product'=>$product->id])}}')
                .then(function () {//请求成功后执行
                    swal('收藏成功','','success')
                },function (error) {//请求失败后会执行这个回调
                    //如果返回码是401 代表没有登录
                    if(error.response && error.response.status===401){
                        swal('请先登录','','error');
                    }else if(error.response && error.response.data.msg){
                        swal(error.response.data.msg,'','error');
                    }else{
                        swal('系统出错了','','error');
                    }

                })
        })
        //取消收藏
        $('.btn-disfavor').click(function () {
            axios.delete('{{route('products.disfavor',['product'=>$product->id])}}')
                .then(function () {//请求成功后执行
                    swal('执行成功','','success').then(function () {
                        location.reload();
                    })

                },function (error) {
                    console.log(error);
                })
        })
        //添加购物车事件
        $('.btn-add-to-cart').click(function () {
            //请求加入购物车接口
            axios.post('{{route('cart.add')}}',{sku_id:$('label.active input[name=skus]').val(),
                amount: $('.cart_amount input').val(),
            }).then(function () {//d成功后
                swal('加入购物车成功','','success').then(function () {
                    location.href="{{route('cart.index')}}";
                })
            },function (error) {
                if(error.response.status===401){
                    swal('请先登录','','error');
                }else if(error.response.status ==422){
                    // http 状态码为 422 代表用户输入校验失败
                    var html = '<div>';
                    _.each(error.response.data.errors, function (errors) {
                        _.each(errors, function (error) {
                            html += error+'<br>';
                        })
                    });
                    html += '</div>';
                    swal({content: $(html)[0], icon: 'error'})
                }else{
                    // 其他情况应该是系统挂了
                    swal('系统错误', '', 'error');
                }
            })
        })

    </script>
    @endsection