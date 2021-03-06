<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                <span class="sr-only">Toggle Navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ url('/') }}">
                Laravel Shop
            </a>
            <ul class="navbar-nav mr-auto">
                <!-- 顶部类目菜单开始 -->
                <!-- 判断模板是否有 $categoryTree 变量 -->
                @if(isset($categoryTree))
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="categoryTree">所有类目 <b class="caret"></b></a>
                        <ul class="dropdown-menu" aria-labelledby="categoryTree">
                            <!-- 遍历 $categoryTree 集合，将集合中的每一项以 $category 变量注入 layouts._category_item 模板中并渲染 -->
                            @each('layouts._category_item', $categoryTree, 'category')
                        </ul>
                    </li>
            @endif
            <!-- 顶部类目菜单结束 -->
            </ul>
        </div>
        <div  id="app-navbar-collapse">

            <ul class="nav navbar-nav navbar-right">
                @guest
                    {{--如果是游客则显示--}}
                    <li><a href="{{ url('login') }}">登录</a></li>
                    <li><a href="{{ route('register') }}">注册</a></li>
                @else

                    <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                    <span class="user-avatar pull-left" style="margin-right:8px; margin-top:-5px;">
                    <img src="https://iocaffcdn.phphub.org/uploads/images/201709/20/1/PtDKbASVcz.png?imageView2/1/w/60/h/60" class="img-responsive img-circle" width="30px" height="30px">
                    </span>
                    {{Auth::user()->name}} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                    <li>
                    <a href="{{ route('logout') }}"
                    onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                    退出登录
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    {{ csrf_field() }}
                    </form>
                    </li>
                        <li>
                            <a href="{{route('user_addresses.index')}}">收货地址</a>
                        </li>
                        <li>
                            <a href="{{route('products.favorites')}}">我的收藏</a>
                        </li>
                        <li>
                            <a href="{{route('cart.index')}}">我的购物车</a>
                        </li>
                        <li>
                            <a href="{{route('orders.index')}}">我的订单</a>
                        </li>
                        <li>
                            <a href="{{route('installments.index')}}">分期付款</a>
                        </li>
                    </ul>
                    </li>

                    @endguest

            </ul>
        </div>
    </div>
</nav>