<?php

namespace App\Admin\Controllers;

use App\Models\Announcement;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class AnnounceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '公告管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Announcement());
        $grid->model()->orderBy('id','DESC');
        $grid->column('id', __('ID'))->sortable();
        $grid->column('uuid', 'uuid');
        $grid->column('title', '标题');
        $grid->column('content', '内容')->display(function ($content){
            return strip_tags($content);
        });
        $grid->column('url', '链接');
        $grid->column('created_at', __('创建时间'));

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->like('uuid', 'uuid');
            $filter->equal('title', '标题');
            $filter->between('created_at', '创建时间')->datetime();
        });
        $url = config('app.url') . '/inner/announce';
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
            Announcement::insert([
                'uuid'=>md5(time()),
                'title'=>$data['title'],
                'content'=>$data['content'],
                'display_type'=>$data['display_type'],
                'url'=>$data['url']
            ]);
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
            Announcement::where('id',$id)->update([
                'title'=>$data['title'],
                'content'=>$data['content'],
                'display_type'=>$data['display_type'],
                'url'=>$data['url']
            ]);
        }catch (\Exception $e){
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
        $form = new Form(new Announcement);

        $form->display('id', __('ID'));
        $form->text('title', '公告标题')->required();
        $form->ckeditor('content', '公告内容');
        $form->radio('display_type', '查看方式')->options([1 => '新窗口打开', 2 => '内部页面打开'])->default(1);
        $form->url('url', '公告链接');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
