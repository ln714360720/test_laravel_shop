<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstallmentsController extends Controller
{
    //
    
    public function index(Request $request)
    {
        $installments=Installment::query()
            ->where('user_id',$request->user()->id)
            ->paginate(10);
        return view('installments.index',compact('installments'));
    }
    //显示详情页面
    public function show(Installment $installment){
      $this->authorize('own',$installment);
        //取出当前分期付款的所有的还款计划,并按还款顺序
        $items=$installment->items()->orderBy('sequence')->get();
        
        return view('installments.show',[
            'installment'=>$installment,
            'items'=>$items,
            'nextItem'=>$items->where('paid_at',null)->first()
        ]);
    }
}
