<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendReviewRequest extends FormRequest
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
            'reviews'=>['required','array'],
            'review.*.id'=>[
                'required',
                Rule::exists('order_items')->where('order_id',$this->route('order')),
            ],
            'reviews.*.rating'=>['required','integer','between:1,5'],
            'reviews.*.review'=>['required'],
        ];
    }
    
    public function attributes()
    {
        return [
            'reviews.*.rating'=>'评分',
            'reviews.*.review'=>'评价',
            
        ];
    }
    
}
