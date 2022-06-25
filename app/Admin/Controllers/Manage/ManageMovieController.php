<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\MovieActorAss;
use App\Models\MovieDirectorAss;
use DLP\DLPViewer;
use App\Admin\Extensions\FileInput;
use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieCategory;
use Elasticsearch\ClientBuilder;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        Admin::headerJs(config('app.url') . '/jsonview/jquery.jsonview.min.js');
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

        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('number', '番号')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('name', '名称')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        //$grid->column('small_cover', '封面图')->image('', 100, 100);
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();
        $grid->column('cid', '分类')->using($categories);
        $grid->column('is_up', '状态')->using([1 => '上架', 2 => '下架']);

        $grid->column('release_time', __('发布时间'))->sortable();
        $grid->column('created_at', __('创建时间'))->sortable();
        $grid->column('updated_at', __('创建时间'))->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) use ($categories) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->equal('number', '番号');
            $filter->equal('cid', '分类')->select($categories);
            $filter->between('release_time', '发布时间')->datetime();
            $filter->between('created_at', '发布时间')->datetime();
        });

        $url = config('app.url') . '/inner/manage_movie';
        DLPViewer::makeHeadPlaneAction($grid, [
            ['document_id' => 'CAF', 'title' => '新增', 'url' => $url . '/create', 'xhr_url' => $url]
        ]);
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST'],
            ['document_class' => 'octopus', 'title' => '朔源', 'url' => config('app.url') . '/inner/manage_movie/octopus/{id}']
        ], ['view']);
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
        $content = $content
            ->body($this->form());
        return DLPViewer::makeForm($content);
    }

    public function store()
    {
        $request = Request::capture();
        $data = $request->all();
        return DLPViewer::result(false, '失败信息');
    }


    public function edit($id, Content $content)
    {
        /*return $content
            ->header($this->title . '-修改')
            ->description($this->title . '-修改')
            ->body($this->form($id)->edit($id));*/
        $content = $content
            ->body($this->form($id)->edit($id));
        return DLPViewer::makeForm($content);
    }

    public function update($id)
    {
        try {
            $request = Request::capture();
            $data = $request->all();
            $update = [];
            $movie = Movie::where('id', $id)->first();
            DB::beginTransaction();
            $this->fluxLinkage((array)json_decode($data['flux_linkage'], true), $movie, $update);
            $this->upDirector((int)$data['director'], $movie, $director_is_up);
            $this->upActors($data['actors'], $movie, $actor_is_up);
            $update = array_merge($update, [
                'name' => $data['name'],
                'cid' => (int)$data['category'],
                'time' => (int)$data['time'],
                'release_time' => $data['release_time'],
                'is_up' => (int)$data['is_up'],
                'is_hot' => (int)$data['is_hot'],
                'is_subtitle' => (int)$data['is_subtitle']
            ]);
            Movie::where('id', $id)->update($update);
            $this->upEs($movie, [
                'name' => $data['name'],
                'big_cove' => $movie->big_cove,
                'small_cover' => $movie->small_cover,
                'release_time' => $data['release_time'],
                'is_up' => (int)$data['is_up'],
                'is_hot' => (int)$data['is_hot'],
                'is_subtitle' => (int)$data['is_subtitle'],
                'categoty_id' => (int)$data['category'],
                'categoty_name' => (MovieCategory::where('id', (int)$data['category'])->first())->name,
                'is_download' => $update['is_download']
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return DLPViewer::result(false, $e->getMessage());
        }
        DB::commit();
        return DLPViewer::result(true);
    }

    private function fluxLinkage($flux_linkage, $movie, &$update)
    {
        $tempFlux_linkage = [];
        $now = time();
        foreach ($flux_linkage as $v) {
            if (!isset($v['name']) || !isset($v['url'])) {
                continue;
            }
            $arr = array();
            $arr['name'] = $v['name'] ?? '';
            $arr['url'] = $v['url'] ?? '';
            $arr['meta'] = isset($v['meta']) ? $v['meta'] : '';
            $arr['tooltip'] = $v['tooltip'] == 1 ? 1 : 2;
            $arr['time'] = $v['time'] && $v['time'] != '' ? $v['time'] : date('Y-m-d H:i:s', $now);
            $arr['is-small'] = $v['is-small'] == 1 ? 1 : 2;
            $arr['is-warning'] = $v['is-warning'] == 1 ? 1 : 2;
            if ($arr['time'] > strtotime($movie->new_comment_time)) {
                $update['new_comment_time'] = date('Y-m-d H:i:s', $now);
            }
            $tempFlux_linkage[] = $arr;
        }
        $num = count($tempFlux_linkage);
        if ($num !== $movie->flux_linkage_num) {
            $update['flux_linkage_num'] = $num;
        }
        if ($num > 0 && $movie->is_download == 1) {
            $update['is_download'] = 2;
        } else {
            $update['is_download'] = 1;
        }
        $update['flux_linkage'] = json_encode($tempFlux_linkage);
    }

    private function upDirector($director_id, $movie, &$director_is_up = false)
    {
        if ($director_id > 0) {
            return;
        }
        $movieDirectorAss = MovieDirectorAss::where('mid', $movie->id)->first();
        if ($movieDirectorAss) {
            if ($movieDirectorAss->did == $director_id) {
                return;
            }
            $director_is_up = true;
            MovieDirectorAss::where('id', $movieDirectorAss->id)->update(['status' => 1, 'did' => $director_id]);
        } else {
            $director_is_up = true;
            MovieDirectorAss::insert(['did' => $director_id, 'mid' => $movie->id]);
        }
    }

    private function upActors($actors, $movie, &$actor_is_up = false)
    {
        $insert = (array)json_decode($actors['insert'], true);
        $delete = (array)json_decode($actors['delete'], true);
        if (!empty($insert)) {
            $actor_is_up = true;
            $up = [];
            foreach ($insert as $aid => $name) {
                $up[] = ['mid' => $movie->id, 'aid' => $aid];
            }
            MovieActorAss::insert($up);
        }
        if (!empty($delete)) {
            $actor_is_up = true;
            $ids = array_keys($delete);
            MovieActorAss::where('mid', $movie->id)->whereIn('aid', $ids)->delete();
        }
    }

    private function upEs($movie, array $update)
    {
        $ES = ClientBuilder::create()->setHosts([env('ELASTIC_HOST') . ':' . env('ELASTIC_PORT')])->build();
        $record = $ES->exists([
            'index' => 'movie',
            'type' => '_doc',
            'id' => $movie->id,
        ]);
        if (!$record) {
            $update['pv'] = 0;
            $update['number'] = $movie->number;
            $update['status'] = 1;
            $update['id'] = $movie->id;
            $ES->index([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $movie->id,
                'body' => $update
            ]);
            return;
        }
        $ES->update([
            'index' => 'movie',
            'type' => '_doc',
            'id' => $movie->id,
            'body' => [
                'doc' => $update
            ]
        ]);
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
        $director = MovieDirectorAss::where('status', 1)->where('mid', $id)->first();
        $director_id = $director ? $director->did : 0;
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();

        $form->tab('基础信息', function ($form) use ($movie, $categories, $director_id) {
            $form->text('name', '名称')->required();
            $form->text('number', '番号')->readonly();
            $form->radio('is_hot', '热门')->options([1 => '普通', 2 => '热门']);
            $form->radio('is_subtitle', '字幕')->options([1 => '不含', 2 => '含字幕']);
            $form->radio('is_up', '展示状态')->options([1 => '上架', 2 => '下架']);
            $form->number('time', '影片时长');
            $form->select('category', '类别')->options($categories)->default($movie ? $movie->cid : '');
            $form->select('director', '导演')->options('/inner/getDirectors')->default($director_id);
            $form->datetime('release_time', '发行时间')->format('YYYY-MM-DD HH:mm:ss');
        });

        $form->tab('磁链信息', function ($form) use ($movie) {
            $flux_linkage = $movie ? (array)json_decode($movie->flux_linkage, true) : [];
            DLPViewer::makeComponentLine($form, 'flux_linkage', '磁力链接', $flux_linkage, [
                'columns' => [
                    'name' => ['name' => '名称', 'type' => 'input'],
                    'meta' => ['name' => '信息', 'type' => 'input'],
                    'url' => ['name' => '链接', 'type' => 'input'],
                    'time' => ['name' => '更新时间', 'type' => 'text'],
                    'is-small' => ['name' => '高清[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'is-warning' => ['name' => '含字幕[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'tooltip' => ['name' => '可下载[1是 2否]', 'type' => 'input', 'style' => 'width:60px']]
            ]);
        });

        $form->tab('演员/标签', function ($form) use ($movie) {
            /*演员选择器*/
            if ($movie) {
                list($actors, $selected_actors) = $this->actorMultiSelect($movie->id, $movie->cid);
            } else {
                list($actors, $selected_actors) = [[], []];
            }
            DLPViewer::makeComponentDot($form, 'actors', '演员选择', $actors, $selected_actors,['strict'=>true]);

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
            if ($movie) {
                foreach ((array)json_decode($movie->map, true) as $m) {
                    $map[] = $m['img'];
                }
            }
            FileInput::files($form, 'map', '组图', $map, $settings);
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
            ->orderBy('movie_actor.id', 'DESC')
            ->take(50)
            ->pluck('movie_actor.name', 'movie_actor.id')->all();

        $selected = MovieActor::join('movie_actor_associate', 'movie_actor.id', '=', 'movie_actor_associate.aid')
            ->where('movie_actor.status', 1)
            ->where('movie_actor_associate.mid', $id)
            ->pluck('movie_actor.name', 'movie_actor.id')->all();

        foreach ($selected as $key => $value) {
            if (!isset($select[$key])) {
                $select[$key] = $value;
            }
        }

        return [$select, $selected];
    }
}
