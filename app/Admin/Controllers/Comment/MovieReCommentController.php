<?php


namespace App\Admin\Controllers\Comment;


use App\Admin\Actions\Post\BatchMovieCommentHide;
use App\Models\MovieComment;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Controllers\AdminController;

class MovieReCommentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '回复列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MovieComment);
        $grid->model()->with('movie');
        $grid->model()->with('user_client');
        $grid->model()->with(['user_lock' => function ($query) {
            $query->orderby('id' ,'desc');
        }]);
        $grid->model()->where('type', 2);
        $grid->model()->orderBy("id", "desc");

        $grid->column('id', __('ID'))->sortable();
        $grid->column('uid', __('用户ID'));
        $grid->column('user_client.nickname', __('用户名'));
        $grid->column('comment', __('内容'));
        $grid->column('audit', __('审核状态'))->using([
            0 => '待审核', 1 => '正常', 2 => '不通过']);
        $grid->column('status', __('显示状态'))->switch([
            'on'  => ['value' => 1, 'text' => '显示', 'color' => 'success'],
            'off' => ['value' => 2, 'text' => '隐藏', 'color' => 'default'],
        ]);

        $grid->column('created_at', __('评论时间'));

        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉查看
            $actions->disableEdit();
        });

        /*查询匹配*/
        $grid->filter(function ($filter) {

            $filter->equal('uid', '用户ID');
            $filter->like('user_client.nickname', '用户名');
            $filter->equal('user_client.status', "用户状态")->select([1 => '正常', 2 => '禁言', 3 => '拉黑']);
            $filter->equal('audit', "审核状态")->select([0 => '待审核', 1 => '正常', 2 => '不通过']);
            $filter->between('created_at', '创建时间')->datetime();
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new BatchMovieCommentHide());
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
        $form = new Form(new MovieComment);

        $form->display('id', __('ID'));
        $form->radio('status', '显示')->options([
            '1' => '显示',
            '2' => '隐藏'
        ]);
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }


}
