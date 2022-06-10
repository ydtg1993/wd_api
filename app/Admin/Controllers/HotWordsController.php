<?php


namespace App\Admin\Controllers;


use App\Http\Controllers\Controller;
use App\Models\HotWords;
use App\Models\SearchLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;

class HotWordsController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '热词管理';


    public function index(Content $content)
    {
        return $content
            ->title("热词管理")
            ->row(function (Row $row){
                $row->column(7,  $this->grid());
                $row->column(5, function (Column $column){
                    $m = HotWords::get()->first();
                    $form = new \Encore\Admin\Widgets\Form(new HotWords());
                    $form->action(admin_url('hotwords/store'));

                    $form->textarea("content", "热搜词")->default($m->content ?? "");
                    $form->hidden('id', "ID")->default($m->id ?? "");
                    $form->disableReset();
                    $column->append((new Box("编辑", $form))->style('success'));
                });
            });
    }

    public function store()
    {
        $id = request()->get('id');
        if ($id){
            $m = HotWords::where('id', $id)->first();
        }else{
            $m = new HotWords();
        }
        $m->content = request()->get("content");
        $m->save();

        admin_toastr('操作成功...', 'success');
        return response()->redirectTo('admin/hotwords');

    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SearchLog);
        $grid->model()->groupBy('content');
        $grid->model()->select(DB::raw("min(id) as id, content as keyword, count(1) as num , max( created_at ) AS created_at"));


        $grid->column('id', __('ID'))->sortable();
        $grid->column('keyword', __('热搜词'));
        $grid->column('num', __('搜索次数'))->sortable();
        $grid->column('created_at', __('更新时间'));


//            $grid->column('updated_at', __('Updated at'));
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();


        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉查看
            $actions->disableView();
            // 去掉编辑
            $actions->disableEdit();

        });

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('content', '热搜词');
        });

        return $grid;
    }

}
