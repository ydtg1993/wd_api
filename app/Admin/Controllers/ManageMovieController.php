<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ComponentViewer;
use App\Admin\Extensions\FileInput;
use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieCategory;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

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
        Admin::headerJs(config('app.url') . '/component.js?v' . rand(0, 100));
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
        ComponentViewer::makeAddFormAction($grid);
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

    public function create(Content $content)
    {
        $request = Request::capture();
        if ($request->ajax()) {
            $data = $request->all();
            return ComponentViewer::result(false, '失败信息');
        }
        $content = $content
            ->body($this->form());
        return ComponentViewer::makeForm($content);
    }


    public function edit($id, Content $content)
    {
        $request = Request::capture();
        if ($request->ajax()) {
            $data = $request->all();
            $files = $_FILES;
            return ComponentViewer::result(true);
        }
        $content = $content
            ->body($this->form($id)->edit($id));
        return ComponentViewer::makeForm($content);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = '')
    {
        $form = new Form(new Movie);
        $movie = Movie::where('id', $id)->first();
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();

        $form->tab('基础信息', function ($form) use ($categories) {
            $form->text('name', '名称')->required();
            $form->text('number', '番号')->readonly();
            $form->radio('is_hot', '热门')->options([1 => '普通', 2 => '热门']);
            $form->radio('is_subtitle', '字幕')->options([1 => '不含', 2 => '含字幕']);
            $form->radio('is_up', '展示状态')->options([1 => '上架', 2 => '下架']);
            $form->number('time', '影片时长');
            $form->select('category', '类别')->options($categories);
            $form->select('director', '导演');
            $form->datetime('release_time', '发行时间')->format('YYYY-MM-DD HH:mm:ss');
        });

        $form->tab('磁链信息', function ($form) use ($movie) {
            $flux_linkage = $movie ? (array)json_decode($movie->flux_linkage, true) : [];
            ComponentViewer::makeComponentLine($form, 'flux_linkage', '磁力链接', [
                'name' => ['name' => '名称', 'type' => 'input', 'style' => 'width:120px'],
                'meta' => ['name' => '信息', 'type' => 'input'],
                'url' => ['name' => '链接', 'type' => 'input', 'style' => 'width:120px'],
                'time' => ['name' => '更新时间', 'type' => 'text'],
                'is-small' => ['name' => '是否高清[1是 2否]', 'type' => 'input'],
                'is-warning' => ['name' => '含字幕[1是 2否]', 'type' => 'input'],
                'tooltip' => ['name' => '可下载[1是 2否]', 'type' => 'input'],
            ], $flux_linkage);
        });

        $form->tab('演员/标签', function ($form) use ($movie) {
            /*演员选择器*/
            if ($movie) {
                list($actors, $selected_actors) = $this->actorMultiSelect($movie->id, $movie->cid);
            } else {
                list($actors, $selected_actors) = [[], []];
            }
            ComponentViewer::makeComponentDot($form, 'actors', '演员选择', $actors, $selected_actors);

            /*标签择器*/
            //TODO
            $form->html('<div id="labels"></div>', '标签选择');
        });

        /*图像资源管理*/
        $form->tab('图像资源', function ($form) use ($movie) {
            $settings = [
                'uploadUrl' => config('app.url') . '/admin/manage_movie/fileInput',
                'uploadExtraData' => [
                    '_token' => csrf_token(),
                    '_method' => 'POST',
                    'movie_id' => $movie ? $movie->id : '',
                    'uploadAsync' => $movie ? true : false
                ]
            ];

            $settings['uploadExtraData']['column'] = 'big_cove';
            $form->image('big_cove', '大封面')->options($settings)->removable()->uniqueName();

            $settings['uploadExtraData']['column'] = 'small_cover';
            $form->image('small_cover', '小封面')->options($settings)->removable()->uniqueName();

            $settings['uploadExtraData']['column'] = 'trailer';
            $settings['allowedFileExtensions'] = ["mp4", "mpg", "mpeg", "avi", "rmvb"];
            $settings['maxFileSize'] = 51200;
            $form->file('trailer', '预告片')->options($settings)->removable()->uniqueName();

            $settings = [
                'uploadUrl' => config('app.url') . '/admin/manage_movie/map',
                'uploadExtraData' => [
                    '_token' => csrf_token(),
                    'movie_id' => $movie ? $movie->id : '',
                    'uploadAsync' => $movie ? true : false
                ],
                'deleteUrl' => config('app.url') . '/admin/manage_movie/fileRemove',
                'deleteExtraData' => [
                    '_token' => csrf_token(),
                    'movie_id' => $movie ? $movie->id : '',
                    'uploadAsync' => $movie ? true : false
                ]
            ];
            $settings['uploadExtraData']['column'] = 'map';
            $settings['allowedFileExtensions'] = ["png", "jpg", "jpeg", "gif"];
            $settings['maxFileCount'] = 20;
            $settings['maxFileSize'] = 5120;
            $map = [];
            if($movie){
                foreach ((array)json_decode($movie->map,true) as $m){
                    $map[] = $m['img'];
                }
            }
            FileInput::files($form, 'map', '组图',$map, $settings);
        });
        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }

    private function actorMultiSelect($id, $cid)
    {
        $select = MovieActor::join('movie_actor_category_associate', 'movie_actor.id', '=', 'movie_actor_category_associate.aid')
            ->where('movie_actor_category_associate.cid', $cid)
            ->where('movie_actor.status', 1)
            ->pluck('movie_actor.name','movie_actor.id')->all();

        $selected = MovieActor::join('movie_actor_associate', 'movie_actor.id', '=', 'movie_actor_associate.aid')
            ->where('movie_actor.status', 1)
            ->where('movie_actor_associate.mid', $id)
            ->pluck('movie_actor.name','movie_actor.id')->all();

        return [$select, $selected];
    }
}
