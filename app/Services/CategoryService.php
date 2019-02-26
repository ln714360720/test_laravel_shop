<?php
namespace App\Services;

use App\Models\Category;

class CategoryService{
    /**
     * 这是一个递归方法
     * @param null $parentId 要获取子类目的父类目id,null代表获取所有的根类目
     * @param null $allCategories 获取数据库里的所有的类目,如果是null代表需要从数据库中查出
     */
    public function getCategoryTree($parentId=null,$allCategories=null){
        if(is_null($allCategories)){
            $allCategories=Category::all();//从数据库里一次性取出所有类目
        }
        return $allCategories->where('parent_id',$parentId)->map(function(Category $category)use ($allCategories){
            $data=['id'=>$category->id,'name'=>$category->name];
            //如果当前类目不是父类目,则直接返回
            if(!$category->is_directory){
    
                return $data;
            }
            
            //否则递归用这本方法,将值放入到children字段里
            $data['children']=$this->getCategoryTree($category->id,$allCategories);
            return $data;
        });
    }
    
}