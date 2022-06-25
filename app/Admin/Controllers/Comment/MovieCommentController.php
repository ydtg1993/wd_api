<?php


namespace App\Admin\Controllers\Comment;


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
        $grid->model()->where('type', 1);
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
        $grid->column('status', __('显示状态'))->switch([
            'on'  => ['value' => 1, 'text' => '显示', 'color' => 'success'],
            'off' => ['value' => 2, 'text' => '隐藏', 'color' => 'default'],
        ]);

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
        $grid->column('created_at', __('评论时间'));

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();
        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('movie.number', '番号');
            $filter->equal('uid', '用户ID');
            $filter->like('user_client.nickname', '用户名');
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
        $form = new Form(new MovieComment);
        $form->display('id', __('ID'));
        $form->radio('status', '显示')->options([
            '1' => '显示',
            '2' => '隐藏'
        ]);

        return $form;
    }
}
