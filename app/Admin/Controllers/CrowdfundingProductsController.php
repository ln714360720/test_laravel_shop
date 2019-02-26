<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\CrowdfundingProduct;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CrowdfundingProductsController extends Controller
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
            ->header('列表')
            ->description(' ')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('详情')
            ->description(' ')
            ->body($this->detail($id));
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
            ->header('编辑')
            ->description(' ')
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
            ->header('新增')
            ->description(' ')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product);
        $grid->model()->where('type',\App\Models\Product::TYPE_CROWDFUNDING)->with(['category']);
        $grid->id('编号');
        $grid->title('商品名称');
        $grid->on_sale('上架状态')->display(function ($value){
            return $value ? '是':'否';
        });
        $grid->price('价格');
        $grid->column('category.name','类目');
        //展示众筹相关字段
        $grid->column('crowdfunding.target_amount','目标金额');
        $grid->column('crowdfunding.end_at','结束时间');
        $grid->column('crowdfunding.total_amount','目前金额');
        $grid->column('crowdfunding.status','状态')->display(function ($value){
            return CrowdfundingProduct::$statusMap[$value];
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools){

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
        $show = new Show(Product::findOrFail($id));
        
        $show->id('Id');
        $show->type('Type');
        $show->category_id('Category id');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->title('Title');
        $show->description('Description');
        $show->image('Image');
        $show->on_sale('On sale');
        $show->rating('Rating');
        $show->sold_count('Sold count');
        $show->review_count('Review count');
        $show->price('Price');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product);
        //在表单里添加一个名type,值为product::TYPE_CROWDFUNDING的隐藏字段
        $form->hidden('type')->value(Product::TYPE_CROWDFUNDING);
        $form->text('title', '商品名称')->rules('required');
        $form->select('category_id','类目')->options(function($id){
           $category=Category::find($id);
           if($category){
               return [$category->id=>$category->name];
           }
        })->ajax('/admin/api/categories?is_directory=0')->rules('required');
        $form->image('image', '封面图片')->rules('required|image');
        $form->editor('description','商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1'=>'是','0'=>'否'])->default(0);
        //添加众筹相关字段
        $form->text('crowdfunding.target_amount','众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at','众筹结束时间')->rules('required|date');
        $form->hasMany('skus', function (Form\NestedForm $form){
            $form->text('title','sku名称')->rules('required');
            $form->text('description','sku描述')->rules('required');
            $form->text('price','价格')->rules('required|numeric|min:0.01');
            $form->text('stock','库存')->rules('required|integer|min:0');
        });
        $form->saving(function (Form $form) {
            $form->model()->price=collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price');
        });
        return $form;
    }
}
