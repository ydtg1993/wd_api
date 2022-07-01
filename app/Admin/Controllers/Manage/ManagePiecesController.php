<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\Movie;
use App\Models\MoviePieceList;
use App\Models\MovieSeriesAss;
use App\Models\PieceListMovie;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagePiecesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '片单管理';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MoviePieceList());
        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '片单名称')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('intro', '简介')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('status', '状态')->using([1 => '正常', 2 => '弃用']);
        $grid->column('uid', '用户名')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('authority', '可见')->using([1 => '公开', 2 => '私有']);
        $grid->column('type', '类型')->using([1 => '用户创建', 2 => '管理员', 3 => '用户默认']);
        $grid->column('created_at', __('创建时间'))->sortable();
        $grid->column('updated_at', __('更新时间'))->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->between('created_at', '创建时间')->datetime();
        });
        $url = config('app.url') . '/inner/manage_pieces';
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
            $selected = PieceListMovie::where('plid', $id)->pluck('mid')->all();
            list($insert, $delete) = CommonController::dotCalculate($selected, $data['numbers']);
            if (!empty($insert)) {
                foreach ($insert as &$v) {
                    $mid = $v;
                    $v = ['mid' => $mid, 'plid' => $id];
                }
                PieceListMovie::insert($insert);
            }
            if (!empty($delete)) {
                PieceListMovie::where('series_id', $id)->whereIn('mid', $delete)->delete();
            }
            $up = [
                'name' => $data['name'],
                'status' => $data['status'],
                'intro'=>$data['intro'],
                'movie_sum' => MoviePieceList::where('plid', $id)->count()];
            if($request->hasFile('photo')){
                $up['cover'] = CommonController::upload(
                    $request->file('photo'),
                    MoviePieceList::class,
                    $id,
                    'cover',
                    'movie_piece_list',64);
            }
            MoviePieceList::where('id', $id)->update($up);
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
        $form = new Form(new MoviePieceList());
        $form->model()->with('numbers');
        $form->text('name', '名称')->required();
        $form->textarea('intro', '简介')->rows(3);
        $form->radio('status', '状态')->options([1 => '正常', 2 => '弃用'])->default(1);
        $form->image('cover', '照片')->removable()->uniqueName();
        $form->multipleSelect('numbers', '关联番号')->options(function ($ids) {
            return Movie::whereIn('id', $ids)->pluck('number as text', 'id')->all();
        })->ajax('/inner/searchNumbers');

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
