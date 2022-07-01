<?php

namespace App\Admin\Controllers;

use App\Console\Commands\RankList;
use App\Http\Controllers\Controller;
use App\Models\CommConf;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->row(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::environment());
                    $column->append(Dashboard::dependencies());
                });

                $row->column(6, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form();
                    $form->action(admin_url('home/switch'));
                    $val = Redis::get(CommConf::COMMENT_SWITCH_KEY);
                    $form->switch('comment_switch', '评论开关')->states([
                        'on'  => ['value' => 1, 'text' => '打开', 'color' => 'success'],
                        'off' => ['value' => 2, 'text' => '关闭', 'color' => 'danger'],
                    ])->default($val ?? 1);

                    $form->disableReset();

                    $column->append((new Box("评论开关", $form))->style('success'));

                    $url = config('app.url').'/inner/cache/clearcache/';
                    $html = <<<EOF
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="0">首页列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="10">广告位</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="9">首页轮播图</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="7">公共配置</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="1">演员影片列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="2">系列影片列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="3">片商影片列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="4">片单影片列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="5">标签分类列表</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="6">影片排行榜列表(慢)</btn>
<btn class="btn btn-info btn-sm cache-index-refresh" data-content="8">演员排行榜列表(慢)</btn>
<script>
$('.cache-index-refresh').click(function() {
  var id = $(this).attr('data-content');
    $.ajax({
        method: 'post',
        url: '{$url}'+id,
        data: {
            _method:'post',
            _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        success: function (data) {
            console.log(data);
            if (data.code === 200){
                $.admin.toastr.success(data.msg, '', {positionClass:"toast-top-center"});
            }else{
                $.admin.toastr.error(data.msg, '', {positionClass:"toast-top-center"});
            }
        }
    });
})
</script>
EOF;
                    $column->append((new Box("网站缓存", $html))->style('success'));

                });


            });
    }


    public function switch(Request $request)
    {
        if ($request->method() == 'POST') {
            $on = $request->input('comment_switch');
            $on = $on == 'on' ? 1 : 2;
            Redis::set(CommConf::COMMENT_SWITCH_KEY, $on);
        }
        admin_toastr('操作成功...', 'success');
        return response()->redirectTo('/admin');
    }

    public function clearCache($type, Request $request)
    {
        switch ($type) {
            case 0:
                $this->clearAll('home:*');
                break;
            case 1:
                $this->clearAll('actor_detail_products:*');
                break;
            case 2:
                $this->clearAll('series_detail_products:*');
                break;
            case 3:
                $this->clearAll('film_company_detail_products:*');
                break;
            case 4:
                $this->clearAll('number_detail_products:*');
                break;
            case 5:
                $this->clearAll('movie:lists:catecory:*');
                $this->clearAll('movie:count:catecory:*');
                break;
            case 6:
                (new RankList())->movie();
                break;
            case 7:
                $this->clearAll('Conf:*');
                break;
            case 8:
                (new RankList())->actor();
                break;
            case 9:
                $this->clearAll('carousel:*');
                break;
            case 10:
                $this->clearAll('ads_list:*');
                break;
            default:
                return response()->json([
                    'code' => 404,
                    'msg' => '找不到此类型...'
                ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => '缓存清除成功'
        ]);
    }

    public function clearAll($cache)
    {
        $prefix = config('database.redis.options.prefix');
        $keys = Redis::keys($cache);
        foreach ($keys as $key) {
            Redis::del(str_replace($prefix, '', $key));
        }
    }
}
