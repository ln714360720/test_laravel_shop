<?php

namespace App\Http\Requests;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CrowdFundingOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sku_id'=>['required',
            function($attributes,$value,$fail){
                if(!$sku=ProductSku::query()->find($value)){
                    return  $fail('该商品不存在');
                }
                //众筹商品下单接吕公支持众筹商品的sku
                if($sku->product->type !==Product::TYPE_CROWDFUNDING){
                    return $fail('该商品不支持众筹');
                }
                //下架
                if(!$sku->product->on_sale){
                    return $fail('该商品未上架');
                }
                //判断众筹的状态,只有是在众筹中的才可以下单
                if($sku->product->crowdfunding->status !==CrowdfundingProduct::STATUS_FUNDING)
                {
                    return $fail('该商品众筹时间已结束');
                }
                //判断库存是否充足
                if($sku->stock===0){
                    return $fail('该商品已售完');
                }
                //判断购买数量,库存是否充足
                if($this->input('amount')>0 && $sku->stock < $this->input('amount')){
                    return $fail('该商品库存不足');
                }
                
            }
                ],
            'amount'=>['required','integer','min:1'],
            'address_id'=>[
                'required',
                Rule::exists('user_addresses','id')->where('user_id',$this->user()->id),
            ],
        ];
    }
}
