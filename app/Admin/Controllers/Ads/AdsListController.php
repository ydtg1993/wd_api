<?php

namespace App\Admin\Controllers\Ads;

use App\Admin\Controllers\CommonController;
use App\Models\Ads;
use App\Models\AdsList;
use App\Models\AdsPos;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '广告列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Ads());
        $ads_pos = AdsPos::where('status', 1)->pluck('name', 'location')->all();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '广告名称');
        $grid->column('photo', '广告图')->image('', 100, 100);
        $grid->column('sort', '权重');
        $grid->column('location', '广告位置')->using($ads_pos);
        $grid->column('status', '状态')->using([
            1 => '上架',
            2 => '下架',
            3 => '到期']);
        $grid->column('end_time', __('到期时间'));
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) use ($ads_pos) {
            // 在这里添加字段过滤器
            $filter->like('name', '广告名称');
            $filter->equal('location', '广告位置')->select($ads_pos);
            $filter->equal('status', '状态')
                ->radio([
                    '' => '全部',
                    1 => '上架',
                    2 => '下架',
                    3 => '到期']);
            $filter->between('end_time', '到期时间')->datetime();
        });
        $url = config('app.url') . '/inner/ads_list';
        DLPViewer::makeHeadPlaneAction($grid, [
            ['document_id' => 'CAF', 'title' => '新增', 'url' => $url . '/create', 'xhr_url' => $url]
        ]);
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST'],
        ], ['edit', 'view']);
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
        try {
            $id=AdsList::insertGetId([
                'name' => $data['name'],
                'remark' => $data['remark'],
                'location' => $data['location'],
                'url' => $data['url'],
                'sort' => $data['sort'],
                'is_close' => $data['is_close'],
                'status' => $data['status'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'adminer'=> Auth::id()
            ]);
            if($request->hasFile('photo')){
                $photo = CommonController::upload(
                    $request->file('photo'),
                    AdsList::class,
                    $id,
                    'photo',
                    'advertisement');
                AdsList::where('id', $id)->update(['photo'=>$photo]);
            }
        } catch (\Exception $e) {
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
                'name' => $data['name'],
                'remark' => $data['remark'],
                'location' => $data['location'],
                'url' => $data['url'],
                'sort' => $data['sort'],
                'is_close' => $data['is_close'],
                'status' => $data['status'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'adminer'=> Auth::id()
            ];
            if($request->hasFile('photo')){
                $up['photo'] = CommonController::upload(
                    $request->file('photo'),
                    AdsList::class,
                    $id,
                    'photo',
                    'advertisement');
            }
            AdsList::where('id', $id)->update($up);
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
        $form = new Form(new Ads);
        $ads_pos = AdsPos::where('status', 1)->pluck('name', 'location')->all();

        $form->display('id', __('ID'));
        $form->text('name', '广告名称')->required();
        $form->text('remark', '描述');
        $form->select('location', '位置')->options($ads_pos)->default('left');
        $form->image('photo', '图片');
        $form->url('url', '广告链接');
        $form->number('sort', '权重');
        $form->radio('is_close', '可关闭')->options([1 => '可关', 2 => '不可'])->default(1);
        $form->radio('status', '状态')->options([
            1 => '上架',
            2 => '下架',
            3 => '到期'])->default(1);
        $form->datetime('start_time', '开启时间');
        $form->datetime('end_time', '结束时间');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }

}
