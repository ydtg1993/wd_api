<?php

namespace App\Admin\Controllers;

use App\Models\Ads;
use App\Models\AdsPos;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class AdsListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '广告列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Ads());
        $ads_pos = AdsPos::where('status',1)->pluck('name','location')->all();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '广告名称');
        $grid->column('photo', '广告图')->image('', 100, 100);
        $grid->column('sort', '权重');
        $grid->column('location', '广告位置')->using($ads_pos);
        $grid->column('status', '状态')->using([
            1=>'上架',
            2=>'下架',
            3=>'到期']);
        $grid->column('sort', '权重');
        $grid->column('end_time', __('到期时间'));
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function($filter)use($ads_pos){
            // 在这里添加字段过滤器
            $filter->like('name', '广告名称');
            $filter->equal('location', '广告位置')->select($ads_pos);
            $filter->equal('status', '状态')
                ->radio([
                    ''=>'全部',
                    1=>'上架',
                    2=>'下架',
                    3=>'到期']);
            $filter->between('end_time', '到期时间')->datetime();
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
        $show = new Show(Ads::findOrFail($id));

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
        $ads_pos = AdsPos::where('status',1)->pluck('name','location')->all();

        $form->display('id', __('ID'));
        $form->text('name', '广告名称')->required();
        $form->text('remark', '描述');
        $form->select('location', '位置')->options($ads_pos)->default('left');
        $form->image('photo', '图片');
        $form->url('url', '广告链接');
        $form->number('sort', '权重');
        $form->radio('is_close', '可关闭')->options([1 => '可关', 2 => '不可'])->default(1);
        $form->radio('status', '状态')->options([
            1=>'上架',
            2=>'下架',
            3=>'到期'])->default(1);
        $form->datetime('start_time', '开启时间');
        $form->datetime('end_time', '结束时间');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
