<?php

namespace App\Admin\Controllers;

use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Request;
use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrdersController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('订单列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show(Order $order,Content $content)
    {
        return $content->header('查看订单')->body(view('admin.orders.show',['order'=>$order]));

    }
    
    /**发货处理
     * @param Order   $order
     * @param Request $request
     */
    public function ship(Order $order,Request $request){
        //判断当前订单是否已支付
        if(!$order->paid_at){
            throw new InvalidRequestException('该订单未付款');
        }
        //判断当前订单是否已发货
        if($order->ship_status !==Order::SHIP_STATUS_PENDING){
            throw new InvalidRequestException('该订单已发货');
        }
        //众筹订单只有在众筹成功之后才可以发货
        if($order->type===Order::TYPE_CROWDFUNDING && $order->items[0]
        ->product->crowdfunding->status !==CrowdfundingProduct::STATUS_SUCCESS
        ){
            throw  new InvalidRequestException('众筹订单只能在众筹成功后发货');
        }
        //laravel5.5之后,validate 方法可以返回校验过的值
        $data=$this->validate($request, [
            'express_company'=>['required'],
            'express_no'=>['required'],
        ],[ ],[
            'express_company'=>'物流公司',
            'express_no'=>'物流单号',
        ]);
        //将订单发货状态改为已发货,并存入物流信息
        $order->update([
           'ship_status'=>Order::SHIP_STATUS_DELIVERED,
           'ship_data'=>$data,//我们在order模型里$casts属性里指定了ship_data 为json格式,这里可以直接把数组入进去
        ]);
        //返回上一页
        return redirect()->back();
    }


    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        //只显示已支付的订单,并且默认按支付时间倒序排序
        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at','desc');
        $grid->no('订单流水号');
        //展示关联关系的字段时,使用column方法
        $grid->column('user.name','买家');
        $grid->address('Address');
        $grid->total_amount('总金额')->sortable();
        $grid->remark('备注');
        $grid->paid_at('支付时间')->sortable();
        $grid->payment_method('支付方式');
        $grid->payment_no('支付订单号');
        $grid->ship_status('物流')->display(function ($value){
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('退款状态')->display(function ($value){
           return Order::$refundStatusMap[$value];
        });
        
        $grid->created_at('创建时间');
        //禁用创建按钮,后台不需要创建订单
        $grid->disableCreateButton();
        $grid->actions(function ($actions){
            //禁用删除和编辑按钮
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->tools(function ($tools){
            //禁用批量删除按钮
            $tools->batch(function ($batch){
                $batch->disableDelete();
            });
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->id('Id');
        $show->no('No');
        $show->user_id('User id');
        $show->address('Address');
        $show->total_amount('Total amount');
        $show->remark('Remark');
        $show->paid_at('Paid at');
        $show->payment_method('Payment method');
        $show->payment_no('Payment no');
        $show->refund_status('Refund status');
        $show->refund_no('Refund no');
        $show->closed('Closed');
        $show->reviewed('Reviewed');
        $show->ship_status('Ship status');
        $show->ship_data('Ship data');
        $show->extra('Extra');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order);

        $form->text('no', 'No');
        $form->number('user_id', 'User id');
        $form->textarea('address', 'Address');
        $form->decimal('total_amount', 'Total amount');
        $form->textarea('remark', 'Remark');
        $form->datetime('paid_at', 'Paid at')->default(date('Y-m-d H:i:s'));
        $form->text('payment_method', 'Payment method');
        $form->text('payment_no', 'Payment no');
        $form->text('refund_status', 'Refund status')->default('pending');
        $form->text('refund_no', 'Refund no');
        $form->switch('closed', 'Closed');
        $form->switch('reviewed', 'Reviewed');
        $form->text('ship_status', 'Ship status')->default('pending');
        $form->textarea('ship_data', 'Ship data');
        $form->textarea('extra', 'Extra');

        return $form;
    }
    
    public function handleRefund(Order $order,Request $request,OrderService $service)
    {
        //验证提交数据,验证数据比较少,就在这验证了
        $this->validate($request, ['agree'=>['required','boolean'],'reason'=>['required_if:agree,false']],[
            'agree.required'=>'请选择同意','agree.boolean'=>'按钮返回值不合法','reason.required_if'=>'不同意时,请填写退款理由!'
        ],[
            'agree'=>'同意',
            'reason'=>'理由'
        ]);
        //判断订单状态是否正确
        if($order->refund_status !== Order::REFUND_STATUS_APPLIED){
            throw new InvalidRequestException('订单状态不正确');
        }
        if($request->input('agree')){
            //同意退款的逻辑
            //1.先清空拒绝理由
            $extra=$order->extra?:[];
            unset($extra['refund_disagree_reason']);
            $order->update([
                'extra'=>$extra,
            ]);
            //调用退款逻辑
            $service->_refundOrder($order);
        }else{
            //将不同意退款的理由放入到extra字段里
            $extra=$order->extra?:[];
            $extra['refund_disagree_reason']=$request->input('reason');
            //将订单的退款状态改为未退款
            $order->update([
               'refund_status'=>Order::REFUND_STATUS_PENDING,
               'extra'=>$extra,
            ]);
        }
        return $order;
    }
    
    /**执行退款逻辑
     * @param $order
     */
    
}
