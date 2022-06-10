<?php


namespace App\Admin\Controllers;


use App\Models\Complaint;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class ComplaintController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '意见反馈';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Complaint);
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('avid', __('影片id'));
        $grid->column('topic', __('主题'));
        $grid->column('title', __('标题'));
        $grid->column('content', __('具体描述'));
        $grid->column('created_at', __('创建时间'));
        $grid->column('device', __('登录设备'));
        $grid->column('connect', __('联系方式'));
        $grid->column('remark', __('备注'));

        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉编辑
            $actions->disableEdit();

        });

        $grid->disableBatchActions();
        $grid->disableActions();

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->equal('topic', '主题');
            $filter->equal('title', '标题');
            $filter->between('created_at', '创建时间')->datetime();
        });

        return $grid;
    }



    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Complaint);

        $form->display('id', __('ID'));
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
