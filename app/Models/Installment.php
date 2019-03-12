<?php

namespace App\Models;

use App\Models\InstallmentItem;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    //
    const STATUS_PENDING='pending';
    const STATUS_REPAYING='repaying';
    const STATUS_FINISHED='finished';
    public static $statusMap=[
        self::STATUS_PENDING=>'未执行',
        self::STATUS_FINISHED=>'已完成',
        self::STATUS_REPAYING=>'还款中'
    ];
    protected $fillable=[
        'no','total_amount','count','fee_rate','fine_rate','status'
    ];
    protected static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        //监听模型创建事件,在写入到数据库之前触发
        static::creating(function ($model){
            //如果模型里的no字段为空
            if(!$model->no){
                //调用findAvailableNo生成分期流水号
                $model->no=static::findAvailableNo();
                //如果生成失败,则终止创建订单
                if(!$model->no){
                    return false;
                }
            }
        });
    }
    //定义关联关系
    public function user(){
        return $this->belongsTo(User::class);
    }
    //定义与订单的关联关系
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    //定义与分期详情表的关联关系
    public function items()
    {
        return $this->hasMany(InstallmentItem::class);
    }
    public static  function  findAvailableNo(){
        //分期流水呈前缀
        $prefix=date('YmdHis');
        for($i=0;$i<10;$i++){
            //生成随机的6位数
            $no=$prefix.str_pad(random_int(0, 999999), 6,'0',STR_PAD_LEFT);
            //判断是否存在
            if(!static::query()->where('no',$no)->exists()){
                return $no;
            }
        }
       
        return false;
    }
    
    public function refreshRefundStatus()
    {
        $allSuccess=true;
        $this->load('items');//重新加载items 保证与数据库数据同步
        foreach ($this->items as $item) {
            if ($item->paid_at && $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS) {
                $allSuccess = false;
                break;
            }
        }
        if ($allSuccess) {
            $this->order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        }
    }
}
