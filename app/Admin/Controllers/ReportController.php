<?php

namespace App\Admin\Controllers;

use App\Models\Report;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class ReportController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '举报管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Report());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('uuid', 'uuid');
        $grid->column('u_number', '用户名');
        $grid->column('avid', '关联番号');
        $grid->column('content', '内容');
        $grid->column('reason', '原因');
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();
        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->like('u_number', '用户名');
            $filter->equal('uuid', 'uuid');
            $filter->equal('avid', '关联番号');
            $filter->between('created_at', '到期时间')->datetime();
        });
        return $grid;
    }
}
