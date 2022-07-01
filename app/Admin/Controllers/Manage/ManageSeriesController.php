<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\Movie;
use App\Models\MovieSeries;
use App\Models\MovieSeriesAss;
use DLP\DLPViewer;
use App\Models\MovieCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageSeriesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '系列管理';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MovieSeries);
        $grid->model()->with('categories');
        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '系列名称')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $categories = MovieCategory::where('status',1)->pluck('name','id')->all();
        $grid->column('categories', '分类')->display(function ($vs) use ($text_style,$categories){
            $text = '';
            foreach ($vs as $v){
                if(isset($categories[$v['cid']])){
                    $text.=$categories[$v['cid']].'|';
                }
            }
            $text = rtrim($text,'|');
            return "<span style='{$text_style}' title='{$text}'>{$text}</span>";
        });
        $grid->column('status', '状态')->using([1 => '正常', 2 => '弃用']);
        $grid->column('movie_sum', '影片数量');
        $grid->column('like_sum', '收藏数量');
        $grid->column('created_at', __('创建时间'))->sortable();
        $grid->column('updated_at', __('更新时间'))->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter)use($categories) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->equal('categories.cid','分类')->select($categories);
            $filter->between('created_at', '创建时间')->datetime();
        });
        $url = config('app.url') . '/inner/manage_series';
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST']
        ], ['edit', 'view', 'delete']);
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
        $update = [];
        try {

        } catch (\Exception $e) {
            return DLPViewer::result(false, $e->getMessage());
        }
        return DLPViewer::result(true, '');
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
            DB::beginTransaction();
            $data['numbers'] = array_filter($data['numbers']);
            $selected = MovieSeriesAss::where('series_id', $id)->pluck('mid')->all();
            list($insert, $delete) = CommonController::dotCalculate($selected, $data['numbers']);
            if (!empty($insert)) {
                foreach ($insert as &$v) {
                    $mid = $v;
                    $v = ['mid' => $mid, 'series_id' => $id];
                }
                MovieSeriesAss::insert($insert);
            }
            if (!empty($delete)) {
                MovieSeriesAss::where('series_id', $id)->whereIn('mid', $delete)->delete();
            }
            MovieSeries::where('id', $id)->update([
                'name' => $data['name'],
                'status' => $data['status'],
                'cid' => $data['cid'],
                'movie_sum' => MovieSeriesAss::where('series_id', $id)->count()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return DLPViewer::result(false, $e->getMessage());
        }
        DB::commit();
        return DLPViewer::result(true);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = '')
    {
        $form = new Form(new MovieSeries);
        $form->model()->with('numbers');
        $form->text('name', '名称')->required();
        $form->radio('status', '状态')->options([1 => '正常', 2 => '弃用'])->default(1);
        $form->multipleSelect('numbers', '关联番号')->options(function ($ids) {
            return Movie::whereIn('id', $ids)->pluck('number as text', 'id')->all();
        })->ajax('/inner/searchNumbers');

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
