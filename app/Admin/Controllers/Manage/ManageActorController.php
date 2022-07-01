<?php

namespace App\Admin\Controllers\Manage;

use App\Admin\Controllers\CommonController;
use App\Models\MovieActor;
use App\Models\MovieActorAss;
use App\Models\MovieActorName;
use App\Models\MovieCategory;
use DLP\DLPViewer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageActorController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '演员管理';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MovieActor());
        $grid->model()->with('names')->with('categories');
        $text_style = 'text-overflow: ellipsis; overflow: hidden;display: block; white-space: nowrap; width: 120px;';
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '演员名称')->display(function ($v) use ($text_style) {
            return "<span style='{$text_style}' title='{$v}'>{$v}</span>";
        });
        $grid->column('names', '演员别名')->display(function ($names) use ($text_style) {
            $text = '';
            foreach ($names as $name){
                $text.=$name['name'].'|';
            }
            $text = rtrim($text,'|');
            return "<span style='{$text_style}' title='{$text}'>{$text}</span>";
        });
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();
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
        //$grid->column('photo', '头像')->image('', 60, 60);
        $grid->column('status', __('状态'))->using([1=> '正常', 2 => '弃用']);
        $grid->column('movie_sum', '影片数量');
        $grid->column('like_sum', '收藏数量');
        $grid->column('created_at', __('创建时间'))->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter)use($categories) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->equal('category.cid', '分类')->select($categories);
            $filter->between('created_at', '创建时间')->datetime();
        });
        $url = config('app.url') . '/inner/manage_actor';
        DLPViewer::makeRowPlaneAction($grid, [
            ['document_class' => 'CEF', 'title' => '编辑', 'url' => $url . '/{id}/edit', 'xhr_url' => $url . '/{id}', 'method' => 'POST']
        ], ['edit', 'view','delete']);
        return $grid;
    }

    public function create(Content $content)
    {
        $content = $content
            ->body($this->form());
        return DLPViewer::makeForm($content);
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
            $social_accounts = (array)json_decode($data['social_accounts'],true);
            $social_accounts_data = [];
            foreach ($social_accounts as $account){
                $social_accounts_data[$account['key']] = $account['value'];
            }
            $names = array_filter(explode("\r\n",$data['names']));
            $up = [
                'name'=>$data['name'],
                'status'=>$data['status'],
                'social_accounts'=>json_encode($social_accounts_data),
                'names'=>json_encode($names),
                'movie_sum'=>MovieActorAss::where('aid',$id)->count()
            ];
            if($request->hasFile('photo')){
                $up['photo'] = CommonController::upload(
                    $request->file('photo'),
                    MovieActor::class,
                    $id,
                    'photo',
                    'actor',64);
            }
            MovieActor::where('id', $id)->update($up);
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
    protected function form($id='')
    {
        $form = new Form(new MovieActor());
        $form->text('name', '名称')->required();

        $names = '';
        if($id) {
            $ns = MovieActorName::where('aid', $id)->pluck('name')->all();
            foreach ($ns as $n){
                $names .= trim($n).PHP_EOL;
            }
        }
        $html = <<<EOF
<textarea name="names" class="form-control names" rows="5" placeholder="输入 别名">{$names}</textarea>
EOF;
        $form->html($html,'演员别名');
        $form->radio('status', '状态')->options([1 => '正常', 2 => '弃用'])->default(1);
        $form->radio('sex', '性别')->options(['♀' => '女', '♂'=> '男'])->default('♀');
        $social_accounts = [];
        if($id) {
            $actor = MovieActor::where('id', $id)->first();
            $social_accounts = (array)json_decode($actor->social_accounts);
            $social_accounts_temp = [];
            foreach ($social_accounts as $key=>$value){
                $social_accounts_temp[] = ['key'=>$key,'value'=>$value];
            }
            $social_accounts = $social_accounts_temp;
        }
        DLPViewer::makeComponentLine($form, 'social_accounts', '社交账户', $social_accounts, [
            'columns' => [
                'key' => ['name' => '社交平台', 'type' => 'input'],
                'value' => ['name' => '链接', 'type' => 'input']],
            'strict' => true,
            'height'=> '170px'
        ]);
        $form->image('photo', '照片')->removable()->uniqueName();
        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}
