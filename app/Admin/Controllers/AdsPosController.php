<?php

namespace App\Admin\Controllers;

use App\Models\Ads;
use App\Models\AdsPos;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class AdsPosController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '广告位';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdsPos());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '广告位描述');
        $grid->column('location', '广告位定位');
        $grid->column('status', '状态')->using([
            1=>'启用', 2=>'禁用']);
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableRowSelector();
        $grid->disableExport();
        /*查询匹配*/
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
        $show = new Show(AdsPos::findOrFail($id));

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
        $form = new Form(new Ads);

        $form->display('id', __('ID'));
        $form->text('name', '广告位描述')->required();
        $form->text('location', '广告位定位');
        $form->radio('status', '状态')->options([
            1=>'启用',
            2=>'禁用'])->default(1);
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
