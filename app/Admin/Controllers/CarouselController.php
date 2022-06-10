<?php


namespace App\Admin\Controllers;


use App\Admin\Extensions\ComponentViewer;
use App\Models\Movie;
use App\Models\RecommendMovie;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CarouselController extends AdminController
{
    public function __construct()
    {
        Admin::headerJs(config('app.url') . '/component.js?v' . rand(0, 100));
    }

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '轮播图管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RecommendMovie());
        $grid->model()->with("movie");
        $grid->model()->orderBy('created_at', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('movie.number', __('番号'));
        $grid->column('movie.name', __('片名'));
        $grid->column('photo', __('封面图'))->image();
        $grid->column('category', __('类别'));
        $grid->column('pv', __('昨日点击率'));
        $grid->column('comment_num', __('昨日评论量'));
        $grid->column('want_see', __('昨日想看量'));
        $grid->column('seen', __('昨日看过量'));
        $grid->column('hot', __('合计热度'));
        $grid->column('status', __('状态'))->using([
            0 => '开启',
            1 => '关闭'
        ]);
        $grid->column('mtime', __('生成时间'));
        $grid->column('created_at', __('创建时间'));

        $grid->filter(function ($filter) {
            $filter->select('status', '显示状态')->option([0 => '显示', 1 => '关闭']);
            $filter->between('mtime', '生成时间')->datetime();
            $filter->between('created_at', '创建时间')->datetime();
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        $grid->disableBatchActions();
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
        });
        ComponentViewer::makeAddFormAction($grid);
        ComponentViewer::makeEditFormAction($grid);
        return $grid;
    }

    public function create(Content $content)
    {
        $request = Request::capture();
        if ($request->ajax()) {
            $data = $request->all();
            try{
                $movie = Movie::where('id',$data['mid'])->first();
                if(!$movie){
                    throw new \Exception('未找到影片');
                }
                $id=RecommendMovie::insertGetId([
                    'pv' => $data['pv'],
                    'seen' => $data['seen'],
                    'hot' => $data['hot'],
                    'want_see' => $data['want_see'],
                    'comment_num' => $data['comment_num'],
                    'status' => $data['status'],
                    'ctime' => date('Y-m-d 00:00:00'),
                    'mid' => $movie->id,
                    'category' => $movie->cid
                ]);
                if ($request->hasFile('photo')) {
                    $photo = $this->upload($request->file('photo'), $id);
                    $up['photo'] = $photo;
                    RecommendMovie::where('id', $id)->update(['photo'=>$photo]);
                }
            }catch (\Exception $e){
                return ComponentViewer::result(false, $e->getMessage());
            }
            return ComponentViewer::result(true);
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
            try {
                $up = [
                    'pv' => $data['pv'],
                    'seen' => $data['seen'],
                    'hot' => $data['hot'],
                    'want_see' => $data['want_see'],
                    'comment_num' => $data['comment_num'],
                    'status' => $data['status'],
                ];
                if ($request->hasFile('photo')) {
                    $photo = $this->upload($request->file('photo'), $id);
                    $up['photo'] = $photo;
                }
                RecommendMovie::where('id', $id)->update($up);
            } catch (\Exception $e) {
                return ComponentViewer::result(false, $e->getMessage());
            }
            return ComponentViewer::result(true);
        }
        $content = $content
            ->body($this->form($id)->edit($id));
        return ComponentViewer::makeForm($content);
    }

    private function upload($file, $id)
    {
        $allowed_extensions = ["png", "jpg", "jpeg", "gif", "mpg", "mpeg", "image/gif", "image/jpeg", "image/png", "video/mpeg"];
        $mm = $file->getMimeType();

        //检查文件是否上传完成
        if (!in_array($mm, $allowed_extensions)) {
            throw new \Exception('文件格式错误');
        }
        $base_dir = rtrim(public_path('resources'), '/') . '/';

        $recommend = RecommendMovie::where('id', $id)->first();
        if ($recommend->photo) {
            $old_file = $base_dir . $recommend->photo;
            if (is_file($old_file)) {
                unlink($old_file);
            }
            $newDir = $base_dir . dirname($recommend->photo) . '/';
        } else {
            $newDir = $base_dir . 'recommend_movie/' . $recommend->category . '/';
        }
        if (!is_dir($newDir)) {
            mkdir($newDir, 0777, true);
            chmod($newDir, 0777);
        }
        $newFile = substr(md5($file->getPathname() . time()), 0, 6) . "." . $file->getClientOriginalExtension();
        $res = move_uploaded_file($file->getPathname(), $newDir . $newFile);
        if (!$res) {
            throw new \Exception('文件存储失败');
        }

        return 'recommend_movie/' . $recommend->category . '/' . $newFile;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        //通过监听参数变化 ，查询相应数据并作数据填充
        Admin::script(
            <<<EOF
new Promise((resolve, reject) => {
    while (true){
        if(document.getElementById('number-search') instanceof HTMLElement){
            return resolve();
        }
    }
}).then(function() {
$('#number-search').click(function () {
    $.ajax({
       method: 'post',
       url: '/admin/carousel/searchNumber',
       data: {
           _method:'post',
           number: $('#movie_number').val(),
           _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content')
       },
       success: function (result) {
           if(result.code == 200) {
             $('input[name="pv"]').val(result.data.pv);
             $('input[name="seen"]').val(result.data.seen);
             $('input[name="want_see"]').val(result.data.want_see);
             $('input[name="comment_num"]').val(result.data.comment_num);
             $('input[name="hot"]').val(result.data.hot);
             $('input[name="movie[name]"]').val(result.data.movie_name);
             $('input[name="mid"]').val(result.data.mid);
           }else{
                $.admin.toastr.error("找不到内容，请检测番号", '', {positionClass:"toast-top-center"});
           }
       }
   });
});

function calHot(){
       let pv = $('input[name="pv"]').val();
       let seen = $('input[name="seen"]').val();
       let want_see = $('input[name="want_see"]').val();
       let comment_num = $('input[name="comment_num"]').val();
       let hot = parseInt(pv) + parseInt((seen+want_see)*3) + parseInt(comment_num*5)
       if(hot>10000){
        hot = 10000;
       }
       $('input[name="hot"]').val(hot);
}
$('input[name="pv"]').change(calHot);
$('input[name="seen"]').change(calHot);
$('input[name="want_see"]').change(calHot);
$('input[name="comment_num"]').change(calHot);

});
EOF
        );

        $form = new Form(new RecommendMovie);
        $form->model()->with("movie");

        $form->column(1 / 2, function ($form) {
            if (($b = Route::currentRouteName()) == "admin.carousel.edit") {
                $form->text('movie.number', '番号')->disable();
            } else {
                $form->html(<<<EOF
<div class="input-group">
            <input type="text" id="movie_number" name="movie[number]" value="" class="form-control movie_number_" placeholder="输入 番号"><span class="input-group-btn">
            <button id="number-search" class="btn btn-default" type="button">搜索!</button>
            </span>
        </div>
EOF
                    , '番号');
            }

            $form->text('pv', __('昨日点击'));
            $form->text('seen', __('昨日看过'));
            $form->text('hot', __('热度合计'))->readonly();

        });

        $form->column(1 / 2, function ($form) {

            $form->text('movie.name', __('影片名称'))->readonly();
            $form->text('comment_num', __('昨日评论'));
            $form->text('want_see', __('昨日想看'));
            $form->radio('status', __('轮播状态'))->options([
                0 => '开启',
                1 => '关闭'
            ])->default('0');
            $form->image('photo', __('轮播图'));
        });
        $form->hidden('mid');
        CommonController::disableDetailConf($form);
        return $form;
    }

    /**
     * 根据番号搜索对应的影片，并获取统计数据
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMovieStatistics()
    {
        $param = request()->input();
        $number = $param['number'];

        $movie = Movie::where('number', 'like', '%' . $number . '%')->first();
        if (!$movie) {
            return response()->json([
                'code' => 404
            ]);
        }

        // TODO 假数据
        return response()->json([
            'code' => 200,
            'msg' => '参数测试',
            'data' => [
                'pv' => rand(1, 20),
                'seen' => rand(1, 20),
                'want_see' => rand(1, 20),
                'comment_num' => rand(1, 20),
                'hot' => rand(10, 99),
                'movie_name' => $movie->name,
                'mid' => $movie->id
            ]
        ]);
    }
}
