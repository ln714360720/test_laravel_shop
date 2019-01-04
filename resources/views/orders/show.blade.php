@extends('layouts.app')
@section('title', '查看订单')

@section('content')
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>订单详情</h4>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>商品信息</th>
                            <th class="text-center">单价</th>
                            <th class="text-center">数量</th>
                            <th class="text-right item-amount">小计</th>
                        </tr>
                        </thead>
                        @foreach($order->items as $index => $item)
                            <tr>
                                <td class="product-info">
                                    <div class="preview">
                                        <a target="_blank" href="{{ route('products.show', [$item->product_id]) }}">
                                            <img src="{{ $item->product->image_url }}" width="100px">
                                        </a>
                                    </div>
                                    <div>
            <span class="product-title">
               <a target="_blank"
                  href="{{ route('products.show', [$item->product_id]) }}">{{ $item->product->title }}</a>
             </span>
                                        <span class="sku-title">{{ $item->productSku->title }}</span>
                                    </div>
                                </td>
                                <td class="sku-price text-center vertical-middle">￥{{ $item->price }}</td>
                                <td class="sku-amount text-center vertical-middle">{{ $item->amount }}</td>
                                <td class="item-amount text-right vertical-middle">
                                    ￥{{ number_format($item->price * $item->amount, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4"></td>
                        </tr>
                    </table>
                    <div class="order-bottom">
                        <div class="order-info" style="width: 300px;float:left ;">
                            <div class="line">
                                <div class="line-label">收货地址：</div>
                                <div class="line-value">{{ join(' ', $order->address) }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单备注：</div>
                                <div class="line-value">{{ $order->remark ?: '-' }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单编号：</div>
                                <div class="line-value">{{ $order->no }}</div>
                            </div>
                            {{--输出物流信息--}}
                            <div class="line">
                                <div class="line-label">物流状态:</div>
                                <div class="line-value">{{\App\Models\Order::$shipStatusMap[$order->ship_status]}}</div>
                            </div>
                            {{--如果有物流信息则展示--}}
                            @if($order->ship_data)
                            <div class="line">
                                <div class="line-label">物流信息:</div>
                                <div class="line-value">物流公司:{{$order->ship_data['express_company']}}&nbsp;物流订单号:{{$order->ship_data['express_no']}}</div>
                            </div>
                                @endif
                            {{--退款信息的开始--}}
                            @if($order->paid_at && $order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
                            <div class="line">
                                <div class="line-label">退款状态：</div>
                                <div class="line-value">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">退款理由：</div>
                                <div class="line-value">{{ $order->extra['refund_reason'] }}</div>
                            </div>
                            @endif
                            {{--结束--}}
                        </div>
                        <div class="order-summary text-right">
                            <div class="total-amount">
                                <span>订单总价：</span>
                                <div class="value">￥{{ $order->total_amount }}</div>
                            </div>
                            <div>
                                <span>订单状态：</span>
                                <div class="value">
                                    @if($order->paid_at)
                                        @if($order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                            已支付
                                        @else
                                            {{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}
                                        @endif
                                    @elseif($order->closed)
                                        已关闭
                                    @else
                                        未支付
                                    @endif
                                    {{--如果订单的发货状态为已发货,则展示确认收货按钮--}}
                                        @if($order->ship_status=== \App\Models\Order::SHIP_STATUS_DELIVERED)
                                    <div class="receive-button">
                                        <form action="{{route('orders.received',[$order->id])}}" method="post">
                                            {{csrf_field()}}
                                            <button type="button" id="btn-received" class="btn btn-success btn-sm">确认收货</button>
                                        </form>
                                    </div>
                                            @endif
                                        @if($order->paid_at && $order->refund_status ===\App\Models\Order::REFUND_STATUS_PENDING)
                                    <div class="refund-button">
                                        <button class="btn btn-sm btn-danger" id="btn-apply-refund">申请退款</button>
                                    </div>
                                            @endif

                                </div>
                                @if(isset($order->extra['refund_disagree_reason']))
                                <div>
                                    <span>拒绝退款理由:</span>
                                    <div class="value">{{$order->extra['refund_disagree_reason']}}</div>
                                </div>
                                    @endif
                            </div>
                            @if(!$order->paid_at && ! $order->closed)
                                <div class="payment-buttons">
                                    <a class="btn btn-primary btn-sm"
                                       href="{{ route('payment.alipay', ['order' => $order->id]) }}">支付宝支付</a>
                                    <a class="btn btn-success btn-sm" id="btn-wechat"
                                       href="{{ route('payment.wechat', ['order' => $order->id]) }}">微信支付</a>

                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scriptsAfterJs')
    <script type="text/javascript">
        $(function () {
            $("#btn-wechat").click(function () {
                swal({
                   content:$('<img src="{{ route('payment.wechat', ['order' => $order->id]) }}" />')[0],
                    buttons:['关闭','已完成付款'],
                }).then(function (result) {
                    if(result){
                        location.reload();
                    }
                })
            })
        })
        //确认收货按钮点击事件
        $('#btn-received').click(function () {
            //弹出确认框
            swal({
                title:'确认已经收到商品?',
                icon:'warning',
                bottons:true,
                dangerMode:true,
                buttons:['取消','确认收货']
            }).then(function (ret) {
                //如果点击取消则不做任何操作
                if(!ret){
                    return;
                }
                //ajax提交确认操作
                axios.post('{{route('orders.received',[$order->id])}}').then(function () {
                    location.reload();
                })
            })
        })
        //退款申请按钮
        $('#btn-apply-refund').click(function () {
            swal({
               text:'请输入退款理由',
               content:"input",

            }).then(function (input) {
                //当用户点击swal时弹出框上的按钮时触发这个函数
                if(!input){
                    swal('退款理由不可空','','error');
                    return;
                }
                axios.post('{{route('order.apply_refund',[$order->id])}}',{reason:input})
                    .then(function () {
                    swal('申请退款成功','','success').then(function () {
                        location.reload();
                    })
                }).catch(function (error) {
                    if(error.response.status ==422){
                        //说明request 验证不通过
                        swal(error.response.data.errors.reason[0],'','error');
                    }
                })
            })
        })
    </script>
@endsection