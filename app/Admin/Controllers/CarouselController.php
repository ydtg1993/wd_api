<?php
namespace App\Admin\Controllers;


use App\Models\MovieCategory;
use DLP\DLPViewer;
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
        $categories = MovieCategory::where('status',1)->pluck('name','id')->all();

        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('movie.number', __('番号'))->display(function ($v)use($text_style){
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('movie.name', __('片名'))->display(function ($v)use($text_style){
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('photo', __('封面图'))->image( '',90, 90);
        $grid->column('category', __('类别'))->using($categories);
        $grid->column('pv', __('点击率'));
        $grid->column('comment_num', __('评论量'));
        $grid->column('want_see', __('想看量'));
        $grid->column('seen', __('看过量'));
        $grid->column('hot', __('热度'));
        $grid->column('status', __('状态'))->using([
            0 => '开启',
            1 => '关闭'
        ]);
        $grid->column('ctime', __('生成时间'));
        $grid->column('created_at', __('创建时间'));

        $grid->filter(function ($filter)use($categories) {
            $filter->equal('status', '显示状态')->select([0 => '显示', 1 => '关闭']);
            $filter->equal('category','分类')->select($categories);
            $filter->between('mtime', '生成时间')->datetime();
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $url = config('app.url').'/inner/carousel';
        DLPViewer::makeHeadPlaneAction($grid,[
            ['document_id'=>'CAF','title'=>'新增', 'url'=>$url.'/create', 'xhr_url'=>$url]
        ]);
        DLPViewer::makeRowPlaneAction($grid,[
            ['document_class'=>'CEF','title'=>'编辑', 'url'=>$url.'/{id}/edit', 'xhr_url'=>$url.'/{id}', 'method'=>'POST']
        ],['view','edit','delete']);
        return $grid;
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
                $photo = CommonController::upload(
                    $request->file('photo'),
                    RecommendMovie::class,
                    $id,
                    'photo',
                    'recommend_movie',64);
                RecommendMovie::where('id', $id)->update(['photo'=>$photo]);
            }
        }catch (\Exception $e){
            return DLPViewer::result(false, $e->getMessage());
        }
        return DLPViewer::result(true);
    }

    public function edit($id, Content $content)
    {
        $content = $content
            ->body($this->form($id)->edit($id));
        return DLPViewer::makeForm($content);
    }

    public function update($id)
    {
        $request = Request::capture();
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
                $up['photo'] =  CommonController::upload(
                    $request->file('photo'),
                    RecommendMovie::class,
                    $id,
                    'photo',
                    'recommend_movie');
            }
            RecommendMovie::where('id', $id)->update($up);
        } catch (\Exception $e) {
            return DLPViewer::result(false, $e->getMessage());
        }
        return DLPViewer::result(true);
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
        if(document.getElementById('component') instanceof HTMLElement){
            return resolve();
        }
    }
}).then(function() {
$('#number-search').click(function () {
    $.ajax({
       method: 'post',
       url: '/inner/carousel/searchNumber',
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
