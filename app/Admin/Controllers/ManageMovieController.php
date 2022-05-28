<?php

namespace App\Admin\Controllers;

use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieActorCategoryAssociate;
use App\Models\MovieCategory;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;

class ManageMovieController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '影片管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Movie());
        $grid->model()->where('status', 1);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('number', '番号');
        $grid->column('name', '名称')->display(function ($name) {
            return "<span title='{$name}' style='text-overflow:ellipsis;overflow:hidden;display:block;white-space: nowrap;width: 120px;'>{$name}</span>";
        });
        $grid->column('small_cover', '封面图')->image('', 100, 100);
        $categories = MovieCategory::where('status', 1)->pluck('name', 'id')->all();
        $grid->column('cid', '分类')->using($categories);
        $grid->column('is_up', '状态')->using([1 => '上架', 2 => '下架']);

        $grid->column('release_time', __('发布时间'));
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('创建时间'));
        /*配置*/
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) use ($categories) {
            // 在这里添加字段过滤器
            $filter->like('name', '名称');
            $filter->equal('number', '番号');
            $filter->equal('cid', '分类')->select($categories);
            $filter->between('release_time', '发布时间')->datetime();
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Movie::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        $form = new Form(new Movie);

        Admin::script($this->componentSelect());
        $form->display('id', __('ID'));
        $form->text('name', '名称')->required();

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        HomeController::disableDetailConf($form);
        return $content
            ->header($this->title . '-创建')
            ->description($this->title . '-创建')
            ->body($form);
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $form = new Form(new Movie);
        $movie = Movie::where('id',$id)->first();
        Admin::script($this->componentSelect());

        $form->display('id', __('ID'));
        $form->text('name', '名称')->required();
        /*演员选择器*/
        list($actors, $selected_actors) = $this->actorMultiSelect($movie->id, $movie->cid);
        Admin::script('componentSelect("actors",JSON.parse(\''.$selected_actors.'\'),JSON.parse(\''.$actors.'\'))');
        $form->html('<div id="actors"></div>', '演员选择');

        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        /*配置*/
        HomeController::disableDetailConf($form);
        return $content
            ->header($this->title . '-修改')
            ->description($this->title . '-修改')
            ->body($form);
    }

    private function actorMultiSelect($id, $cid)
    {
        $select = MovieActor::join('movie_actor_category_associate','movie_actor.id','=','movie_actor_category_associate.aid')
            ->where('movie_actor_category_associate.cid',$cid)
            ->where('movie_actor.status',1)
            ->select('movie_actor.id','movie_actor.name','movie_actor.sex')->get()->toArray();

        $selected = MovieActor::join('movie_actor_associate','movie_actor.id','=','movie_actor_associate.aid')
            ->where('movie_actor.status',1)
            ->where('movie_actor_associate.mid',$id)
            ->select('movie_actor.id','movie_actor.name','movie_actor.sex')->get()->toArray();

        return [CommonController::safeJson($select), CommonController::safeJson($selected)];
    }

    private function componentSelect()
    {
        return <<<EOF
function componentSelect(name,selected,options) {
            function tagSelect() {
                var cdom = this.cloneNode(true);
                cdom.addEventListener('click',tagCancel);
                $('#'+name+'-select').append(cdom);
                this.remove();
                addVal();
            }
            function tagCancel() {
                var cdom = this.cloneNode(true);
                cdom.addEventListener('click',tagSelect);
                $('#'+name+'-content').append(cdom);
                this.remove();
                addVal();
            }
            function addVal() {
                var val = '';
                $('#'+name+'-select').children().each(function(i,n){
                    val += parseInt(n.getAttribute('data-id'))+",";
                });
                val = val.replace(/,$/g, '');
                $("input[name="+name+"]").val(val);
            }

            var selected_dom = '';
            var options_dom = '';
            var selected_tag = '';

            for(var i in options){
                var tag_name = options[i];
                tag_name = decodeURI(tag_name);

                if(selected.indexOf(parseInt(i)) > -1){
                    selected_dom+= "<div class='btn btn-success v-tag' data-id='"+i+"'  style='margin-right: 8px;margin-bottom: 8px'>"+tag_name+"</div>";
                    selected_tag+= i + ',';
                    continue;
                }
                options_dom+= "<div class='btn btn-primary v-tag' data-id='"+i+"'  style='margin-right: 8px;margin-bottom: 8px'>"+tag_name+"</div>";
            }
            selected_tag = selected_tag.replace(/,$/g, '');

            var html = '<div style="width: 100%;display: grid; grid-template-rows: 45px 140px;border: 1px solid #ccc;border-radius: 10px">' +
                '                    <div id="'+name+'-select" style="overflow-y: auto;border-bottom: 1px solid #ccc;padding: 5px">' +
                selected_dom+
                '                    </div>' +
                '                    <div id="'+name+'-content" style="overflow-y: auto;padding: 5px">' +
                options_dom +
                '                    </div>' +
                '                    <input type="hidden" name="'+name+'" value='+selected_tag+'>' +
                '                </div>';

            document.getElementById(name).innerHTML = html;
            $('#'+name+'-select .v-tag').click(tagCancel);
            $('#'+name+'-content .v-tag').click(tagSelect);
        }
EOF;
    }
}
