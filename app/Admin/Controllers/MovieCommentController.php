<?php


namespace App\Admin\Controllers;


use App\Models\MovieComment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class MovieCommentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '评论管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MovieComment());
        $grid->model()->with('movie');
        $grid->model()->with('user_client');
        $grid->model()->with(['user_lock' => function ($query) {
            $query->orderby('id' ,'desc');
        }]);
        $grid->model()->orderBy("id", "desc");

        $grid->column('id', __('ID'))->sortable();
        $grid->column('movie.number', __('影片番号'));
        $grid->column('uid', __('用户ID'));

        $grid->column('user_client.nickname', __('用户名'));
        $grid->column('user_lock.status', __('用户状态'))->using([
            1 => '正常', 2 => '禁言', 3 => '拉黑'])->display(function ($query){
            $uid = $this->uid;
            $url = "/admin/locklistdata?uid=" . $uid;
            $unlock_time = $this->user_lock->unlock_time ?? "";
            if (($this->user_lock->status ?? 1) != "1"){
                return "<a href='{$url}'>{$query}({$unlock_time})</a>";
            }
        });

        $grid->column('score', __('评分'));
        $grid->column('comment', __('内容'));
        //1.正常 0.待审核 2.不通过
        $grid->column('audit', __('审核状态'))->using([
            0 => '待审核', 1 => '正常', 2 => '不通过']);
//        $grid->column('like', __('赞数'));

        $grid->column('like', '赞数')->modal('点赞列表', function ($model) {

            $comments = $model->comments()->with("user_client")->where('type' , 1)->get()->map(function ($comment) {
                $id = $comment->id;
                $nickname = $comment->user_client->nickname;
                $created_at = $comment->created_at;
                $login_ip = $comment->user_client->login_ip;
                $uid = $comment->uid;
                return [$id, $uid, $nickname, $created_at, $login_ip];
            });
            $comment = $comments->toArray();

            foreach ($comment as $k => &$v) {
                $v[2] = "<a target='_blank' href='/admin/account?&id=" . $v[1] . "'>{$v[2]}</a>";
            }
            return new Table(['ID', '用户ID', '用户名', '点赞时间', '登录ip'], $comment);
        });


        $grid->column('dislike', __('踩数'));
        $grid->column('updated_at', __('评论时间'));

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

            $actions->append("<a class='btn btn-xs action-btn btn-success grid-row-pass'><i class='fa fa-info' title='详情'>详情</i></a>");

        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('movie.number', '番号');
            $filter->equal('uid', '用户ID');
            $filter->like('user_client.nickname', '用户名');

//            $filter->select('phone', '手机号');
//            $filter->equal('email', '邮箱');

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
        $show = new Show(MovieComment::findOrFail($id));

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
        $form = new Form(new MovieComment);

        $form->display('id', __('ID'));
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
