<?php

namespace App\Admin\Controllers;

use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieCategory;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class ManageMovieController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '影片管理';

    public function __construct()
    {
        Admin::headerJs(config('app.url').'/component.js?v'.rand(0,100));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Movie());
        $grid->model()->where('status', 1);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('number', '番号');
        $grid->column('name', '名称')->display(function ($name) {
            return "<span title='{$name}' style='text-overflow:ellipsis;overflow:hidden;display:block;white-space: nowrap;width: 120px;'>{$name}</span>";
        });
        //$grid->column('small_cover', '封面图')->image('', 100, 100);
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();
        $grid->column('cid', '分类')->using($categories);
        $grid->column('is_up', '状态')->using([1 => '上架', 2 => '下架']);

        $grid->column('release_time', __('发布时间'));
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('创建时间'));
        /*配置*/
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) use ($categories) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->equal('number', '番号');
            $filter->equal('cid', '分类')->select($categories);
            $filter->between('release_time', '发布时间')->datetime();
        });
        ComponentViewer::makeEditFormAction($grid);
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
        $show = new Show(Movie::findOrFail($id));

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
        $form = new Form(new Movie);

        $form->display('id', __('ID'));
        $form->text('name', '名称')->required();

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        HomeController::disableDetailConf($form);
        return $content
            ->header($this->title . '-创建')
            ->description($this->title . '-创建')
            ->body($form);
    }


    public function edit($id, Content $content)
    {
        $content =  $content
            ->body($this->form($id)->edit($id));
        return ComponentViewer::makeForm($content);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id='')
    {
        $form = new Form(new Movie);
        $movie = Movie::where('id',$id)->first();

        $form->column('100%', function ($form)use($movie) {
            $form->text('name', '名称')->required();
        });

        $form->column(1/2, function ($form) {
            $form->text('number', '番号')->readonly();
            $form->text('number_source', '源番号')->readonly();
            $form->text('sell', '卖家')->readonly();
            $form->number('score', '评分')->min(1)->max(10);
            $form->datetime('release_time', '发布时间')->format('YYYY-MM-DD HH:mm:ss');
        });
        $form->column(1/2, function ($form) {
            $form->number('time', '影片时长');
            $form->select('category', '类别');
            $form->select('director', '导演');
            $form->select('companies', '片商');
            $form->select('series', '系列');
        });

        $form->column(1/2, function ($form)use($movie) {
            /*演员选择器*/
            list($actors, $selected_actors) = $this->actorMultiSelect($movie->id, $movie->cid);
            Admin::script(<<<EOF
        componentSelect("actors",JSON.parse('$selected_actors'),JSON.parse('$actors'));
EOF
            );
            $form->html('<div id="actors"></div>', '演员选择');
        });

        $form->column(1/2, function ($form)use($movie) {
            /*标签择器*/
            //TODO
            $form->html('<div id="labels"></div>', '标签选择');
        });

        $form->column(12, function ($form)use($movie) {
            Admin::script(<<<EOF
        componentJsonTable("flux_linkage",{
            'name':{'name':'名称',type:'input',style:'width:200px'},
            'meta':{'name':'数据',type:'text'},
            'url':{'name':'链接',style:'width:200px',type:'hidden'}
            },JSON.parse('$movie->flux_linkage'));
EOF
            );
            $form->html('<div id="flux_linkage"></div>', '磁力链接');
        });

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }

    private function actorMultiSelect($id, $cid)
    {
        $select = MovieActor::join('movie_actor_category_associate','movie_actor.id','=','movie_actor_category_associate.aid')
            ->where('movie_actor_category_associate.cid',$cid)
            ->where('movie_actor.status',1)
            ->select('movie_actor.id','movie_actor.name','movie_actor.sex')->get()->toArray();

        $selected = MovieActor::join('movie_actor_associate','movie_actor.id','=','movie_actor_associate.aid')
            ->where('movie_actor.status',1)
            ->where('movie_actor_associate.mid',$id)
            ->select('movie_actor.id','movie_actor.name','movie_actor.sex')->get()->toArray();

        return [CommonController::safeJson($select), CommonController::safeJson($selected)];
    }
}
