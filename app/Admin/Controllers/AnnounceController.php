<?php

namespace App\Admin\Controllers;

use App\Models\Announcement;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class AnnounceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '公告管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Announcement());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('uuid', 'uuid');
        $grid->column('title', '标题');
        $grid->column('content', '内容')->display(function ($content){
            return strip_tags($content);
        });
        $grid->column('url', '链接');
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->like('uuid', 'uuid');
            $filter->equal('title', '标题');
            $filter->between('created_at', '创建时间')->datetime();
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
        $show = new Show(Announcement::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
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
            ->header($this->title.'-创建')
            ->description($this->title.'-创建')
            ->body($this->form());
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header($this->title.'-修改')
            ->description($this->title.'-修改')
            ->body($this->form($id)->edit($id));
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Announcement);

        $form->display('id', __('ID'));
        $form->text('title', '公告标题')->required();
        $form->ckeditor('content', '公告内容');
        $form->radio('display_type', '查看方式')->options([1 => '新窗口打开', 2 => '内部页面打开'])->default(1);
        $form->url('url', '公告链接');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
