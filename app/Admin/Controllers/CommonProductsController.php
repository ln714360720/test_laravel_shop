<?php
/**
 * Created by PhpStorm.
 * User: xs
 * Date: 2019/2/26
 * Time: 16:27
 */

namespace App\Admin\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
abstract class CommonProductsController extends Controller
{
    //封装index(),create(),edit()方法
    Use HasResourceActions;
    //定义一个抽象方法,返回当前管理的商品类型
    abstract public function getProductType();
    public function index(Content $content)
    {
        return $content
            ->header(Product::$typeMap[$this->getProductType()].'列表')
            ->description(' ')
            ->body($this->grid());
    }
    public function edit($id, Content $content)
    {
        return $content
            ->header(Product::$typeMap[$this->getProductType()].'编辑')
            ->description(' ')
            ->body($this->form()->edit($id));
    }
    public function create(Content $content)
    {
        return $content
            ->header(Product::$typeMap[$this->getProductType()].'新增')
            ->description(' ')
            ->body($this->form());
    }
    //封装grid(),form()方法
    protected function grid()
    {
        $grid = new Grid(new Product());
        $grid->model()->where('type',$this->getProductType())->with(['category']);
        //用户自定义的方法
        $this->customGrid($grid);
        $grid->actions(function ($actions){
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools){
            //禁用批量删除按钮
            $tools->batch(function ($batch){
                $batch->disableDelete();
            });
        });
        return $grid;
    }
    //定义一个抽象方法,各个类型的控制器将实现本方法来定义列表应该展示哪些字段
    abstract public function customGrid(Grid $grid);
    protected function form()
    {
        $form = new Form(new Product);
        $form->hidden('type')->value($this->getProductType());
        $form->text('title', '商品名称')->rules('required');
        $form->text('long_title','商品长标题')->rules('required');
        $form->select('category_id', '类目')->options(function ($id) {
            $category = Category::find($id);
            if ($category) {
                return [$category->id => $category->full_name];
            }
        })->ajax('/admin/api/categories?is_directory=0');
        $form->image('image', '封面图片')->rules('required|image');
        $form->editor('description', '商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1' => '是', '0' => '否'])->default('0');
    
        // 调用自定义方法
        $this->customForm($form);
    
        $form->hasMany('skus', '商品 SKU', function (Form\NestedForm $form) {
            $form->text('title', 'SKU 名称')->rules('required');
            $form->text('description', 'SKU 描述')->rules('required');
            $form->text('price', '单价')->rules('required|numeric|min:0.01');
            $form->text('stock', '剩余库存')->rules('required|integer|min:0');
        });
        $form->hasMany('properties','商品属性', function (Form\NestedForm $form){
           $form->text('name','属性名')->rules('required');
           $form->text('value','属性值')->rules('required');
        });
        $form->saving(function (Form $form) {
            $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });
    
        return $form;
    }
    abstract public function customForm(Form $form);
    
}