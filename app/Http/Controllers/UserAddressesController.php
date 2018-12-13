<?php

namespace App\Http\Controllers;
use App\Exceptions\ApiException;
use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class UserAddressesController extends Controller
{
    public function index(Request $request)
    {
        return view('user_addresses.index',['addresses'=>$request->user()->addresses]);
    }
    
    public function create()
    {
        return view('user_addresses.create_and_edit',['address'=>new UserAddress()]);
    }
    
    /**用户地址添加操作
     * @param UserAddressRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserAddressRequest $request)
    {
           
           
           $request->user()->addresses()->create($request->only([
                'province','city','district','address','zip','contact_name','contact_phone'
            ]));
            return redirect()->route('user_addresses.index');
       
    }
    
    public function edit(UserAddress $user_address)
    {
        $this->authorize('own',$user_address);
        return view('user_addresses.create_and_edit',['address'=>$user_address]);
    }
    
    /**收货地址修改操作
     * @param UserAddress        $userAddress 用户模型
     * @param UserAddressRequest $userAddressRequest  验证用户请求数据类
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserAddress $userAddress,UserAddressRequest $userAddressRequest)
    {
        $this->authorize('own',$userAddress);
        $res=$userAddress->update($userAddressRequest->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));
        
        return redirect()->route('user_addresses.index');
    }
    
    public function destroy(UserAddress $userAddress)
    {
        $this->authorize('own',$userAddress);
        $userAddress->delete();
        return [];
    }
}
