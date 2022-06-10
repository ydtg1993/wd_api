<?php


namespace App\Admin\Controllers;


use App\Admin\Actions\Post\ArticleCommentHide;
use App\Admin\Actions\Post\BatchArticleCommentHide;
use App\Admin\Actions\Post\LockAction;
use App\Models\Article;
use App\Models\ArticleComment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\DropdownActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ArticleCommentControllers extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '评论列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ArticleComment());
        $grid->setActionClass(DropdownActions::class);

        $grid->model()->with("article");
        $grid->model()->with("user_client");


        $grid->column('id', __('ID'))->sortable();
        $grid->column('article.id', __('话题ID'));
        $grid->column('article.title', __('话题标题'));
        $grid->column('user_client.nickname', __('用户名'))->display(function ($row){
            return "<a target='_blank' href='/admin/account?&id=" . $this->uid . "'>{$row}</a>";
        });
        //1.正常 0.待审核 -1.不通过'
        $grid->column('audit', __('审核状态'))->using([1 => '正常', 0 => '待审核', -1 =>'不通过']);
        //1.正常 2.删除
        $grid->column('status', __('显示状态'))->using([1 => '显示', 2 => '隐藏']);
        $grid->column('comment', __('评论记录'));
        $grid->column('comment_time', __('评分时间'));

        /*配置*/
//        $grid->disableCreateButton();
//        $grid->disableExport();
//        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉编辑
//            $actions->disableEdit();
            // 自定义解封
            $actions->add(new ArticleCommentHide());
            // 拉黑
            $actions->add(new LockAction());
        });
        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->equal('article.id', '话题ID');
            $filter->like('user_client.nickname', '用户名');
            $filter->select('status', '显示状态')->option([1 => '显示', 2 => '隐藏']);
            $filter->between('created_at', '创建时间')->datetime();
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new BatchArticleCommentHide());
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
        $show = new Show(ArticleComment::findOrFail($id));

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
        $form = new Form(new ArticleComment);

        $form->display('id', __('ID'));
        $form->display('user_client.nickname', __('用户名'));
        $form->text('comment', __('评论记录'));
        $form->display('created_at', __('创建时间'));
        $form->display('updated_at', __('更新时间'));

        return $form;
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        $form = new Form(new ArticleComment());
        Article::all()->pluck("name", "id")->toArray();

        $form->display('id', __('ID'));
        // 话题名称
        $form->select('aid', '话题名称')->options([]);

        $form->textarea('comment', '名称')->required();

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $content
            ->header($this->title . '-创建')
            ->description($this->title . '-创建')
            ->body($form);
    }
}
