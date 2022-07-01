<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\MovieActorAss;
use App\Models\MovieDirectorAss;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use App\Services\DataLogic\DL;
use App\Services\DataLogic\MovieStruct;
use DLP\DLPViewer;
use App\Admin\Extensions\FileInput;
use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieCategory;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
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
        $grid->column('updated_at', __('更新时间'))->sortable();
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
            $filter->between('created_at', '创建时间')->datetime();
        });

        $url = config('app.url') . '/inner/manage_movie';
        $callback = <<<EOF
function(response){
                if (response.code != 0) {
                    _componentAlert(response.message, 3, function () {
                        let element = document.querySelector('#component button[type="submit"]');
                        element.removeAttribute('disabled');
                        element.innerText = '提交';
                    });
                    return;
                }
                this.URL = '{$url}/'+response.data.id+'/edit';
                this.XHR_URL = this.URL;
                this.MODEL_BODY_DOM.innerHTML = '';
                this.makeContent();
}
EOF;

        DLPViewer::makeHeadPlaneAction($grid, [
            ['document_id' => 'CAF', 'title' => '新增', 'url' => $url . '/create', 'xhr_url' => $url, 'callback' => $callback]
        ]);
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST'],
            ['document_class' => 'octopus', 'title' => '朔源', 'url' => config('app.url') . '/inner/manage_movie/octopus/{id}']
        ], ['edit', 'view','delete']);
        return $grid;
    }

    public function create(Content $content)
    {
        $content = $content
            ->body($this->Cform());
        return DLPViewer::makeForm($content);
    }

    public function store()
    {
        $request = Request::capture();
        $data = $request->all();
        $update = [];
        try {
            if($data['name'] == '' || $data['number'] == ''){
                return DLPViewer::result(false, '片名 番号不能为空');
            }
            if(Movie::where('number',$data['number'])->exists()){
                return DLPViewer::result(false, '番号重复');
            }
            $this->fluxLinkage((array)json_decode($data['flux_linkage'], true), null, $update);
            $update = array_merge($update, [
                'name' => $data['name'],
                'number'=>$data['number'],
                'cid' => (int)$data['category'],
                'time' => (int)$data['time'],
                'release_time' => $data['release_time'],
                'is_up' => (int)$data['is_up'],
                'is_hot' => (int)$data['is_hot'],
                'is_subtitle' => (int)$data['is_subtitle']
            ]);
            DB::beginTransaction();
            $id = Movie::insertGetId($update);
            $this->upDirector((int)$data['director'], $id, $director_is_up);
        } catch (\Exception $e) {
            DB::rollBack();
            return DLPViewer::result(false, $e->getMessage());
        }
        DB::commit();
        return DLPViewer::result(true, '', ['id' => $id]);
    }


    public function edit($id, Content $content)
    {
        $content = $content
            ->body($this->form($id)->edit($id));
        return DLPViewer::makeForm($content);
    }

    public function destroy($id)
    {

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
            $this->upDirector((int)$data['director'], $movie->id, $director_is_up);
            $this->upActors($data['actors'], $movie->id, $actor_is_up);
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
            $movie = Movie::where('id', $id)->first();
            $res = DL::getInstance(MovieStruct::class)->update($id,$movie);
            if(!$res){
                DL::getInstance(MovieStruct::class)->store($movie->toArray());
            }
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
            $arr['name'] = $v['name'];
            $arr['url'] = $v['url'];
            $arr['meta'] = $v['meta'];
            $arr['tooltip'] = $v['tooltip'] == 1 ? 1 : 2;
            $arr['time'] = $v['time'] != '' ? date('Y-m-d H:i:s', strtotime($v['time'])) : date('Y-m-d H:i:s', $now);
            $arr['is-small'] = $v['is-small'] == 1 ? 1 : 2;
            $arr['is-warning'] = $v['is-warning'] == 1 ? 1 : 2;
            if (!$movie) {
                $update['new_comment_time'] = date('Y-m-d H:i:s', $now);
                $tempFlux_linkage[] = $arr;
                continue;
            }
            if (strtotime($arr['time']) > strtotime($movie->new_comment_time)) {
                $update['new_comment_time'] = $arr['time'];
            }
            $tempFlux_linkage[] = $arr;
        }
        $num = count($tempFlux_linkage);
        if(!$movie){
            $update['flux_linkage_num'] = $num;
            $update['flux_linkage'] = json_encode($tempFlux_linkage);
            $update['is_download'] = $num>1? 2:1;
            return;
        }
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

    private function upDirector($director_id, $mid, &$director_is_up = false)
    {
        if ($director_id > 0) {
            return;
        }
        $movieDirectorAss = MovieDirectorAss::where('mid', $mid)->first();
        if ($movieDirectorAss) {
            if ($movieDirectorAss->did == $director_id) {
                return;
            }
            $director_is_up = true;
            MovieDirectorAss::where('id', $movieDirectorAss->id)->update(['status' => 1, 'did' => $director_id]);
        } else {
            $director_is_up = true;
            MovieDirectorAss::insert(['did' => $director_id, 'mid' => $mid]);
        }
    }

    private function upActors($actors, $mid, &$actor_is_up = false)
    {
        $actors = array_filter($actors);
        $selected = MovieActorAss::where('mid', $mid)->pluck('aid')->all();
        list($insert, $delete) = CommonController::dotCalculate($selected, $actors);
        if (!empty($insert)) {
            $actor_is_up = true;
            $up = [];
            foreach ($insert as $aid) {
                $up[] = ['mid' => $mid, 'aid' => $aid];
            }
            MovieActorAss::insert($up);
        }
        if (!empty($delete)) {
            $actor_is_up = true;
            MovieActorAss::where('mid', $mid)->whereIn('aid', $delete)->delete();
        }
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id)
    {
        $form = new Form(new Movie);
        $movie = Movie::where('id', $id)->first();
        $director = MovieDirectorAss::where('status', 1)->where('mid', $id)->first();
        $director_id = $director ? $director->did : 0;
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();

        $form->tab('基础信息', function ($form) use ($movie, $categories, $director_id) {
            $form->text('name', '名称')->required();
            $form->text('number', '番号')->readonly();
            $form->radio('is_hot', '热门')->options([1 => '普通', 2 => '热门'])->default(1);
            $form->radio('is_subtitle', '字幕')->options([1 => '不含', 2 => '含字幕'])->default(1);
            $form->radio('is_up', '展示状态')->options([1 => '上架', 2 => '下架'])->default(1);
            $form->number('time', '影片时长')->default(0);
            $form->select('category', '类别')->options($categories)->default($movie->cid);
            $form->select('director', '导演')->options('/inner/getDirectors')->default($director_id);
            $form->datetime('release_time', '发行时间')->format('YYYY-MM-DD HH:mm:ss');
        });

        $form->tab('磁链信息', function ($form) use ($movie) {
            DLPViewer::makeComponentLine($form, 'flux_linkage', '磁力链接', (array)json_decode($movie->flux_linkage,true), [
                'columns' => [
                    'name' => ['name' => '名称', 'type' => 'input'],
                    'meta' => ['name' => '信息', 'type' => 'input'],
                    'url' => ['name' => '链接', 'type' => 'input'],
                    'time' => ['name' => '更新时间', 'type' => 'text'],
                    'is-small' => ['name' => '高清[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'is-warning' => ['name' => '含字幕[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'tooltip' => ['name' => '可下载[1是 2否]', 'type' => 'input', 'style' => 'width:60px']],
                'strict' => true
            ]);
        });

        $form->tab('演员/标签', function ($form) use ($movie) {
            /*演员选择器*/
            $selected_actors = MovieActor::join('movie_actor_associate', 'movie_actor.id', '=', 'movie_actor_associate.aid')
                ->where('movie_actor.status', 1)
                ->where('movie_actor_associate.mid', $movie->id)
                ->pluck('movie_actor.name', 'movie_actor.id')->all();
            $form->multipleSelect('actors', '演员选择')->options(function () use ($selected_actors) {
                return $selected_actors;
            })->ajax('/inner/searchActors');

            /*标签择器*/

        });

        /*图像资源管理*/
        $form->tab('图像资源', function ($form) use ($movie) {
            $settings = [
                'uploadUrl' => config('app.url') . '/admin/manage_movie/fileInput',
                'uploadExtraData' => [
                    '_token' => csrf_token(),
                    '_method' => 'POST',
                    'movie_id' => $movie->id,
                    'uploadAsync' => true
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
                    'movie_id' => $movie->id,
                    'uploadAsync' => true
                ],
                'deleteUrl' => config('app.url') . '/admin/manage_movie/fileRemove',
                'deleteExtraData' => [
                    '_token' => csrf_token(),
                    'movie_id' => $movie->id,
                    'uploadAsync' => true
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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function Cform()
    {
        $form = new Form(new Movie);
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();

        $form->tab('基础信息', function ($form) use ($categories) {
            $form->text('name', '名称')->required();
            $form->text('number', '番号')->required();
            $form->radio('is_hot', '热门')->options([1 => '普通', 2 => '热门'])->default(1);
            $form->radio('is_subtitle', '字幕')->options([1 => '不含', 2 => '含字幕'])->default(1);
            $form->radio('is_up', '展示状态')->options([1 => '上架', 2 => '下架'])->default(1);
            $form->number('time', '影片时长')->default(0);
            $form->select('category', '类别')->options($categories)->default(1);
            $form->select('director', '导演')->options('/inner/getDirectors');
            $form->datetime('release_time', '发行时间')->format('YYYY-MM-DD HH:mm:ss');
        });

        $form->tab('磁链信息', function ($form) {
            DLPViewer::makeComponentLine($form, 'flux_linkage', '磁力链接', [], [
                'columns' => [
                    'name' => ['name' => '名称', 'type' => 'input'],
                    'meta' => ['name' => '信息', 'type' => 'input'],
                    'url' => ['name' => '链接', 'type' => 'input'],
                    'time' => ['name' => '更新时间', 'type' => 'text'],
                    'is-small' => ['name' => '高清[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'is-warning' => ['name' => '含字幕[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                    'tooltip' => ['name' => '可下载[1是 2否]', 'type' => 'input', 'style' => 'width:60px']],
                'strict' => true
            ]);
        });
        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
