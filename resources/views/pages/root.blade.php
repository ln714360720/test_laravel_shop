@extends('layouts.app')
@section('title','首页')
@section('content')
<h1>这里是首页</h1>

<a id="urlId" href="javascript:void(0);" >点击我呀</a>
<a id="urlId" href="{{route('email_verification.send')}}" >点击我呀sss</a>


@endsection()
<script type="text/javascript">
    window.onload = function() {
        $(document).ready(function() {
            /*Append.init();*/
         $("#urlId").click(function () {
            {{--$.get("{{route('email_verification.send')}}",{},function (data) {--}}
               {{--alert(data.msg);--}}
            {{--})--}}
             $.ajax({
                url:"{{route('email_verification.send')}}",
                data:{},
                type:'get',
                dataType:'json',
                success:function (data) {
                    alert(data.msg)
                },
                error:function (data,status) {
                    console.log(data);
                    alert(data.responseJSON.msg);

                }
            })
         })
        });
    };
</script>

