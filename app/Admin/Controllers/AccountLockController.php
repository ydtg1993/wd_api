<?php


namespace App\Admin\Controllers;


use App\Models\UserLock;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Response;

class AccountLockController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '禁用用户列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserLock());
        //  '状态类型，2.禁言  3.拉黑'
        $grid->model()->where('status', ">", 1);


        $grid->column('id', __('ID'));
        $grid->column('uid', __('用户ID'));
        $grid->column('uname', __('用户名'));
        $grid->column('email', __('登录邮箱'));
        $grid->column('phone', __('手机号码'));
        $grid->column('status', __('封禁类型'));
        $grid->column('unlock_time', __('解封时间'));
        $grid->column('remarks', __('封禁原因'));
        $grid->column('updated_at', __('创建时间'));

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉查看
            $actions->disableEdit();
            // 自定义解封
            $actions->append("<a class='btn btn-xs action-btn btn-success grid-row-pass' data-id='{$actions->getKey()}'><i class='fa fa-check' title='用户解封'>解封</i></a>");
        });
        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->equal('uid', '用户ID');
            $filter->like('uname', '用户名');
            $filter->equal('phone', '手机号');
            $filter->equal('email', '邮箱');
            $filter->between('created_at', '创建时间')->datetime();
        });


        /**
         * 创建模态框
         */
        $this->script = <<<EOT
        $('.grid-row-pass').unbind('click').click(function() {
            var id = $(this).data('id');
            swal({
                title: "确认解封吗？",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "确认",
                showLoaderOnConfirm: true,
                cancelButtonText: "取消",
                preConfirm: function() {
                    $.ajax({
                        method: 'get',
                        url: '/admin/unlock/' + id,
                        success: function (data) {
                            $.pjax.reload('#pjax-container');
                            if(data.code){
                                swal(data.msg, '', 'success');
                            }else{
                                swal(data.msg, '', 'error');
                            }
                        }
                    });
                }
            });
        });
EOT;
        Admin::script($this->script);

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
        $show = new Show(UserLock::findOrFail($id));

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
        $form = new Form(new UserLock());

        $form->display('created_at', __('创建时间'));
        $form->display('updated_at', __('修改时间'));

        return $form;
    }

    public function unlock($id)
    {
        $user = UserLock::where('id' , $id)->first();
        $user->status = 1;
        $result = $user->save();
        if ($result){
            return Response::json(['code'=>200,'msg'=>'操作成功','data'=>[]]);
        }else{
            return Response::json(['code'=>500,'msg'=>'操作失败','data'=>[]]);
        }
    }
}
