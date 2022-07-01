<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\MovieCategory;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class ManageCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '分类管理';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MovieCategory());

        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '类别名称')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('status', __('状态'))->switch([
            'on'  => ['value' => 1, 'text' => '正常', 'color' => 'success'],
            'off' => ['value' => 2, 'text' => '弃用', 'color' => 'default'],
        ]);
        $grid->column('show', __('状态'))->switch([
            'on'  => ['value' => 0, 'text' => '显示', 'color' => 'success'],
            'off' => ['value' => 1, 'text' => '不显示', 'color' => 'default'],
        ]);
        $grid->column('sort')->editable()->sortable();
        $grid->column('created_at', __('创建时间'))->sortable();
        $grid->column('updated_at', __('更新时间'))->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->between('created_at', '创建时间')->datetime();
        });
        $grid->disableActions();
        return $grid;
    }

    public function create(Content $content)
    {
        $content = $content
            ->body($this->form());
        return DLPViewer::makeForm($content);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id='')
    {
        $form = new Form(new MovieCategory());
        $form->text('name', '名称')->required();
        $form->radio('status', '状态')->options([1 => '正常', 2 => '弃用'])->default(1);
        $form->radio('show', '状态')->options([1 => '正常', 2 => '弃用'])->default(1);
        $form->text('sort', '排序');
        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
