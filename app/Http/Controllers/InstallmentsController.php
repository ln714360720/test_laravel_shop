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
}