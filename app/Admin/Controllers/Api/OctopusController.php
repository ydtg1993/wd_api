<?php

namespace App\Admin\Controllers\Api;

use DLP\DLPHelper;
use App\Models\CollectionMovie;
use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieActorAss;
use App\Models\MovieCategory;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use DLP\DLPViewer;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\DB;

class OctopusController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '影片管理 爬虫数据比对';

    public function index($id)
    {
        $script = '';
        $movie = Movie::where('id', $id)->first();
        $collection = CollectionMovie::where('id', $movie->oid)->first();
        if (!$collection) {
            return '没有collection';
        }
        $collection = $collection->toArray();
        $number_source = $collection['number_source'];
        $source_site = $collection['source_site'];
        $collection['map'] = json_decode($collection['map'], true);
        $collection['actor'] = json_decode($collection['actor'], true);
        $collection['label'] = json_decode($collection['label'], true);
        $collection['flux_linkage'] = json_decode($collection['flux_linkage'], true);
        $collection['resources_info'] = json_decode($collection['resources_info'], true);
        $collection = DLPHelper::safeJson($collection);
        $script .= <<<EOF
$("#collection").JSONView(JSON.parse('{$collection}'), { collapsed: true, nl2br: true, recursive_collapser: true });
$("#linkage").JSONView(JSON.parse('{$movie->flux_linkage}'), { nl2br: true});
EOF;
        $db = $this->octopusCheckSource($source_site);
        $ori = DB::connection('mongodb')
            ->collection($db)
            ->where('uid', '=', $number_source)
            ->orderBy('_id', 'desc')
            ->first();
        if ($ori) {
            $original = DLPHelper::safeJson($ori);
            $magnet = isset($ori['magnet']) ? DLPHelper::safeJson($ori['magnet']) : [];
            $script .= <<<EOF
$("#ori").JSONView(JSON.parse('{$original}'), { collapsed: true, nl2br: true, recursive_collapser: true });
$("#ori-linkage").JSONView(JSON.parse('{$magnet}'), { nl2br: true});
EOF;
        }

        $html = <<<EOF
<div style="display: grid;grid-template-rows: 15px 50px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #3c8dbc;color: white;line-height: 15px;">基础数据</div>
    <div style="background: white">{$this->basic($movie)}</div>
</div>
<div style="display: grid;grid-template-rows: 15px 50px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #000000;color: white;line-height: 15px;">演员比对表</div>
    <div style="display: grid;grid-template-columns: 50% 50%;padding: 3px;grid-column-gap: 7px;">
        <div style="background: white;overflow-y: auto;">{$this->actors($movie)}</div>
        <div style="background: #f4ffdf;overflow-y: auto;">{$this->oriActors($ori)}</div>
    </div>
</div>
<div style="display: grid;grid-template-rows: 15px 100px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #000000;color: white;line-height: 15px;">标签比对表</div>
    <div style="display: grid;grid-template-columns: 50% 50%;padding: 3px;grid-column-gap: 7px;">
        <div style="background: white;overflow-y: auto;">{$this->labels($movie)}</div>
        <div style="background: #f4ffdf;overflow-y: auto;">{$this->oriLabels($ori)}</div>
    </div>
</div>
<div style="display: grid;grid-template-rows: 15px 250px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #000000;color: white;line-height: 15px;">磁链比对表</div>
    <div style="display: grid;grid-template-columns: 50% 50%;padding: 3px;grid-column-gap: 7px;">
        <div style="background: white;overflow-y: auto;"><div id="linkage"></div></div>
        <div style="background: #f4ffdf;overflow-y: auto;"><div id="ori-linkage"></div>
    </div>
</div>
<div style="display: grid;grid-template-rows: 15px 80px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #000000;color: white;line-height: 15px;">视图资源比对表</div>
    <div style="display: grid;grid-template-columns: 50% 50%;padding: 3px;grid-column-gap: 7px;">
        <div style="background: white;overflow-y: auto;">{$this->files($movie)}</div>
        <div style="background: #f4ffdf;overflow-y: auto;">{$this->oriFiles($ori)}</div>
    </div>
</div>
<div style="display: grid;grid-template-rows: 15px 160px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #dd4b39;color: white;line-height: 15px;">组图比对表</div>
    <div style="display: grid;grid-template-columns: 50% 50%;padding: 3px;grid-column-gap: 7px;">
        <div style="background: white;overflow-y: auto;">{$this->map($movie)}</div>
        <div style="background: #f4ffdf;overflow-y: auto;">{$this->oriMap($ori)}</div>
    </div>
</div>
<div style="display: grid;grid-template-columns: 50% 50%;grid-template-rows: 15px 255px;padding: 3px;grid-column-gap: 7px;">
    <div style="background: #3c8dbc;color: white;line-height: 15px;">collection表</div>
    <div style="background: #3c8dbc;color: white;line-height: 15px;">{$db}表</div>
    <div id="collection" style="height: 250px;overflow: auto;background: #000000d4;color: white"></div>
    <div id="ori" style="height: 250px;overflow: auto;background: #f4ffdf;"></div>
</div>
EOF;
        Admin::script($script);
        return DLPViewer::makeHtml($html);
    }

    private function octopusCheckSource($source_site)
    {
        switch ($source_site) {
            case 'adult.contents.fc2.com':
                $db = 'fc2';
                break;
            case 'javdb.com':
                $db = 'javdb';
                break;
            case 'www.javlibrary.com':
                $db = 'javlibrary';
                break;
            default:
                $db = 'javdb';
        }
        return $db;
    }

    private function basic($movie)
    {
        $category = MovieCategory::where('id', $movie->cid)->first();
        $category = $category ? $category->name : '';
        return <<<EOF
<div style="display: grid;grid-template-columns: 50% 50%;grid-template-rows: 20px 20px;padding: 3px;grid-column-gap: 7px;">
    <div>
        <label for="name" class="col-sm-4 asterisk control-label">名称</label>
        <div>{$movie->name}</div>
    </div>
    <div>
        <label for="name" class="col-sm-4 control-label">番号</label>
        <div>{$movie->number}</div>
    </div>
    <div>
        <label for="name" class="col-sm-4 control-label">类别</label>
        <div>{$category}</div>
    </div>
    <div>
        <label for="name" class="col-sm-4 control-label">发布时间</label>
        <div>{$movie->release_time}</div>
    </div>
</div>
EOF;
    }

    private function actors($movie)
    {
        $aids = MovieActorAss::where('mid', $movie->id)->where('status', 1)->pluck('aid')->all();
        $actors = MovieActor::whereIn('id', $aids)->select('name', 'id', 'sex')->get();
        $html = '<ul>';
        foreach ($actors as $actor) {
            $html .= "<div><div class='col-sm-2'>{$actor->id}</div>{$actor->name} : {$actor->sex}</div>";
        }
        $html .= '</ul>';
        return $html;
    }

    private function oriActors($ori)
    {
        if (!$ori || !isset($ori['actor'])) {
            return '';
        }
        if (!is_array($ori['actor'])) {
            return (string)$ori['actor'];
        }
        $html = '<ul>';
        foreach ($ori['actor'] as $actor) {
            $name = isset($actor[0]) ? $actor[0] : '';
            $sex = isset($actor[1]) ? $actor[1] : '♀';
            $html .= "<div>{$name} : {$sex}<div>";
        }
        $html .= '</ul>';
        return $html;
    }

    private function labels($movie)
    {
        $ids = MovieLabelAss::where('mid', $movie->id)->where('status', 1)->pluck('cid')->all();
        $labels = MovieLabel::whereIn('id', $ids)->select('id', 'name', 'cid')->get();
        $html = '<ul>';
        foreach ($labels as $label) {
            $html .= "<div><div class='col-sm-2'>{$label->id}</div> {$label->name} --{$label->cid}</div>";
        }
        $html .= '</ul>';
        return $html;
    }

    private function oriLabels($ori)
    {
        if (!$ori || !isset($ori['video_sort'])) {
            return '';
        }
        if (!is_array($ori['video_sort'])) {
            return (string)$ori['video_sort'];
        }
        $html = '<ul>';
        foreach ($ori['video_sort'] as $label) {
            $html .= "<li>{$label}</li>";
        }
        $html .= '</ul>';
        return $html;
    }

    private function linkage($movie)
    {

    }

    private function files($movie)
    {
        $big_cove = $movie->big_cove ? config('app.url').$movie->big_cove:'javascript:void(0)';
        $small_cover = $movie->small_cover ? config('app.url').$movie->small_cover :'javascript:void(0)';
        $trailer = $movie->trailer ? config('app.url').$movie->trailer : 'javascript:void(0)';
        return <<<EOF
<ul>
<div>大封面 <a href="{$big_cove}" target="_blank" title="{$big_cove}"> 查看 </a></div>
<div>小封面 <a href="{$small_cover}" target="_blank" title="{$small_cover}"> 查看 </a></div>
<div>预告片 <a href="{$trailer}" target="_blank" title="{$trailer}"> 查看 </a></div>
</ul>
EOF;
    }

    private function oriFiles($ori)
    {
        $big_cover = $ori['big_cover'] ? 'https://'.$ori['big_cover'] : 'javascript:void(0)';
        $small_cover = $ori['small_cover'] ? 'https://'.$ori['small_cover'] : 'javascript:void(0)';
        $trailer = $ori['trailer'] ? 'https://'.$ori['trailer'] : 'javascript:void(0)';
        return <<<EOF
<ul>
<li>大封面 <a href="{$big_cover}" target="_blank" title="{$big_cover}"> 查看 </a></li>
<li>小封面 <a href="{$small_cover}" target="_blank" title="{$small_cover}"> 查看 </a></li>
<li>预告片 <a href="{$trailer}" target="_blank" title="{$trailer}"> 查看 </a></li>
</ul>
EOF;
    }

    private function map($movie)
    {
        $html = '<ul>';
        $map = (array)json_decode($movie->map,true);
        foreach ($map as $k=>$m){
            $big = isset($m['big_img']) ? config('app.url').$m['big_img'] : 'javascript:void(0)';
            $img = isset($m['img']) ? config('app.url').$m['img'] : 'javascript:void(0)';
            $html.=<<<EOF
<div>{$k}. 大图 <a href="{$big}" target="_blank" title="{$big}"> 查看 </a> --- 小图 <a href="{$img}" target="_blank" title="{$img}"> 查看 </a></div>
EOF;
        }
        $html.= '</ul>';
        return $html;
    }

    private function oriMap($ori)
    {
        $html = '<ul>';
        foreach ($ori['preview_img'] as $k=>$m){
            if(isset($ori['preview_big_img']) && isset($ori['preview_big_img'][$k])){
                $big = 'https://'.$ori['preview_big_img'][$k];
            }else{
                $big = 'javascript:void(0)';
            }
            $img = 'https://'.$m;
            $html.=<<<EOF
<div>{$k}. 大图 <a href="{$big}" target="_blank" title="{$big}"> 查看 </a> --- 小图 <a href="{$img}" target="_blank" title="{$img}"> 查看 </a></div>
EOF;
        }
        $html.= '</ul>';
        return $html;
    }
}
