<?php

namespace App\Models;

use App\Models\Installment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class InstallmentItem extends Model
{
    //
    const REFUND_STATUS_PENDING='pending';
    const REFUND_STATUS_PROCESSING='processing';
    const REFUND_STATUS_SUCCESS='success';
    const REFUND_STATUS_FAILED='failed';
    public static $refundStatusMap=[
        self::REFUND_STATUS_PENDING=>'未退款',
        self::REFUND_STATUS_PROCESSING=>'退款中',
        self::REFUND_STATUS_SUCCESS=>'退款成功',
        self::REFUND_STATUS_FAILED=>'退款失败',
    ];
    protected $fillable=[
        'sequence','base','fee','fine','due_date','paid_at','payment_method','payment_no',
        'refund_status'
    ];
    protected $dates=['due_date','paid_at'];
    //定义一个关联关系
    public function installment(){
        return $this->belongsTo(Installment::class);
        
    }
    //定义一个获取器,来返回当前还款计划需要还款的总金额
    public function getTotalAttribute(){
        $total=big_number($this->base)->add($this->fee);
        if(!is_null($this->fine)){
            $total->add($this->fine);
        }
        return $total->getValue();//getValue()是moonoast-math扩展里自带的,返回运算后的值
    }
    //创建一个访问器,返回当前还款谋划是否已过期
    public function getIsOverdueAttribute(){
        return Carbon::now()->gt($this->due_date);
    }
}
