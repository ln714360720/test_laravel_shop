<?php

namespace App\Models;

use App\Exceptions\CouponCodeUnavailableException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CouponCode extends Model
{
    //用常量的方式定义支付的优惠券类型
    const TYPE_FIXED='fixed';
    const TYPE_PERCENT='percent';
    public static $typeMap=[
        self::TYPE_FIXED=>'固定金额',
        self::TYPE_PERCENT=>'百分比',
    ];
    public $fillable=[
        'name','code','type','value','total','used','min_amount',
        'not_before',
        'not_after','enabled',
    ];
    protected $hidden=[
        'id',
    ];
    protected $casts=[
      'enabled'=>'boolean'
    ];
    //指明两个是日期类型
    protected $dates=[
        'not_before','not_after'
    ];
    protected $appends=[
        'description'
    ];
    //获取可用的优惠券码
    public static function findAvailableCode($length=16){
        do{
            $code=strtoupper(Str::random($length));
        }while(self::query()->where('code',$code)->exists());
        return $code;
    }
    //格式化输入内容 满**减** 优惠 ** 格式
    public function getDescriptionAttribute(){
        $str='';
        if($this->min_amount>0){
            $str ='满'.str_replace('.00', '', $this->min_amount);
        }
        if($this->type===self::TYPE_PERCENT){
            return $str.'优惠'.str_replace('.00', '', $this->value).'%';
        }
        return $str.'减'.str_replace('.00','',$this->value);
    }
    
    /** 验证优惠券是否可用
     * @param null $orderAmount
     * @throws CouponCodeUnavailableException
     */
    public function checkAvailable(User $user,$orderAmount=null)
    {
        if(!$this->enabled){
            throw new CouponCodeUnavailableException('优惠券不存在');
        }
        if($this->total-$this->used <=0){
            throw new CouponCodeUnavailableException('该优惠券已被兑完');
        }
        if($this->not_befor && $this->not_befor->gt(Carbon::now())){
            throw new CouponCodeUnavailableException('该优惠券现在还不可使用');
        }
        if($this->not_after && $this->not_after->lt(Carbon::now())){
            throw new CouponCodeUnavailableException('该优惠券已过期');
        }
        if(!is_null($orderAmount) && $orderAmount< $this->min_amount){
            throw new CouponCodeUnavailableException('订单金额不满足优惠券最低金额');
        }
        $used=Order::query()->where('user_id',$user->id)->where('coupon_code_id',$this->id)
            ->where(function ($query){
                $query->where(function ($query){
                   $query->whereNull('paid_at')->where('closed',false);
                })->orWhere(function ($query){
                    $query->whereNotNull('paid_at')->where('refund_status','!=',Order::REFUND_STATUS_SUCCESS);
                });
            })->exists();
        if($used){
            throw new CouponCodeUnavailableException('你已经使用过这个优惠券了!');
        }
    }
    
    /**计算优惠后的金额
     * @param $orderAmount 订单金额
     * @return mixed|string
     */
    public function getAdjustedPrice($orderAmount)
    {
        //固定金额
        if($this->type=== self::TYPE_FIXED){
            return max(0.01,$orderAmount-$this->value);
        
        }
        return number_format($orderAmount*(100-$this->value)/100,2,'.','');
    }
    
    /**新增,减少用量
     * @param bool $increase
     * @return int
     */
    public function changeUsed($increase=true)
    {
        //传入true代表新增用量,否则是减少用量
        if($increase){
            return $this->newQuery()->where('id',$this->id)->where('used','<',$this->total)->increment('used');
        }else{
            return $this->decrement('used');
        }
        
    }
    
}
