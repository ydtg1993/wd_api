<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CommConf;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Form;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;

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

                    $column->append((new Box("快速操作", $form))->style('success'));
                    $column->append((new Box("其它操作"))->style('danger'));

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

    public static function disableDetailConf(&$form)
    {
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
    }
}
