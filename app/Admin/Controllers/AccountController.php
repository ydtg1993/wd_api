<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Post\BatchLock;
use App\Admin\Actions\Post\LockAction;
use App\Admin\Actions\Post\Replicate;
use App\Models\UserClient;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户账户管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserClient());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('number','uuid')->display(function ($number) {
            return "<span class='label label-warning' title='{$number}'>查看</span>";
        });
        $grid->column('reg_device', '设备类型')->using([
            'web' => '电脑网页', 'android' => '安卓手机','iphone'=>'苹果手机',
            'ipad'=> '平板','other'=>'其他']);
        $grid->column('nickname', '用户名');
        $grid->column('phone', '手机号');
        $grid->column('email', '邮箱');
        $grid->column('status', '状态')->using([
            0 => '全部', 1 => '正常',2=>'禁言', 3=> '拉黑']);
        $grid->column('login_time', __('登录时间'));
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
//        $grid->disableBatchActions();
//        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();

            //自定义按钮, 封禁
            $actions->add(new LockAction());
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new BatchLock());
        });

        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->like('nickname', '用户名');
            $filter->equal('status', '状态')
                ->radio(['' => '全部', 1 => '正常',2=>'禁言', 3=> '拉黑']);
            $filter->equal('phone', '手机号');
            $filter->equal('email', '邮箱');
            $filter->between('created_at', '创建时间')->datetime();
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
        $show = new Show(UserClient::findOrFail($id));

        $show->field('number', __('用户ID'));
        $show->field('nickname', __('用户昵称'));
        $show->field('email', __('登录邮箱'));
        $show->field('email', __('用户昵称'));

        $show->field('type', __('用户类型'));
        $show->field('sex', __('性别'));


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
        $form = new Form(new UserClient);

        $form->text('number', __('用户ID'))->disable();
        $form->text('nickname', __('用户昵称'));
        $form->text('email', __('登录邮箱'));

        $form->select('type', "用户类型")->options([1 => "普通用户", 2 => "运营用户", 3 => "vip用户"]);
        $form->select('sex', "性别")->options([0 => "未知", 1 => "男", 2 => "女"]);

        $form->display('created_at', __('创建时间'));
        $form->display('updated_at', __('修改时间'));
        CommonController::disableDetailConf($form);
        return $form;
    }
}
