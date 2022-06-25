<?php

namespace App\Admin\Controllers\Web;


use App\Admin\Controllers\CommonController;
use App\Models\SearchHotWord;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HotWordsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '热词管理';

    protected function grid()
    {
        $grid = new Grid(new SearchHotWord);
        $grid->column('id', __('ID'))->sortable();
        $grid->column('content', '热搜词');
        $grid->column('times', '搜索次数')->sortable();

        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        $url = config('app.url') . '/inner/hotwords';
        DLPViewer::makeHeadPlaneAction($grid, [
            ['document_id' => 'CAF', 'title' => '新增', 'url' => $url . '/create', 'xhr_url' => $url]
        ]);
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST']
        ], ['view','edit']);
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
            SearchHotWord::insert(['content'=>$data['content'],'times'=>$data['times']]);
            Redis::del('hot_keyword');
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
        try{
            SearchHotWord::where('id',$id)->update(['content'=>$data['content'],'times'=>$data['times']]);
            Redis::del('hot_keyword');
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
    protected function form($id = '')
    {
        $form = new Form(new SearchHotWord());
        $form->text('content', '热搜词')->required();
        $form->number('times', '搜索次数');
        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
