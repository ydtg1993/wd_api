<?php


namespace App\Admin\Controllers;


use App\Models\Filter;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class FilterController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '过滤词列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Filter());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('content', __('词'));
        $grid->column('adminer', __('操作者'));
        $grid->column('created_at', __('Created at'));
        $grid->column('adminer', __('操作者'));

//        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->actions(function ($actions) {
            // 去掉删除
//            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉查看
//            $actions->disableEdit();

            $actions->append("<a class='btn btn-xs action-btn btn-success grid-row-pass'><i class='fa fa-info' title='详情'>详情</i></a>");

        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('content', '过滤词');
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Filter::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Filter);

        $form->text('content', __('过滤词'));
        $form->hidden('adminer', __('操作者'));

        $form->saving(function (Form $form){
           $admin_name = Auth::guard('admin')->user();
           $form->adminer = $admin_name->username;
        });

        return $form;
    }
}
