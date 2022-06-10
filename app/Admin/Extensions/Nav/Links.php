<?php

namespace App\Admin\Extensions\Nav;

use Encore\Admin\Admin;

class Links
{
    public function getToken()
    {
        return csrf_token();
    }

    public function __toString()
    {
        Admin::script(
            <<<EOF
$('.cache-index-refresh').on('click', function() {
    var url = $(this).attr('data-content');
    $.ajax({
        method: 'post',
        url: url,
        data: {
            _method:'post',
            _token:'{$this->getToken()}'
        },
        success: function (data) {
            console.log(data);
//            data = JSON.parse(data);
            if (data.code === 200){
//                $.admin.reload();
                $.admin.toastr.success(data.msg, '', {positionClass:"toast-top-center"});
            }else{
                $.admin.toastr.error(data.msg, '', {positionClass:"toast-top-center"});
            }
        }
    });
});
EOF
        );
        return <<<HTML
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        缓存清理
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/0">首页列表</a></li>
                         <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/7">公共配置</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/1">演员影片列表</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/2">系列影片列表</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/3">片商影片列表</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/4">片单影片列表</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/5">标签分类列表</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/6">影片排行榜列表(慢)</a></li>
                        <li><a href="javascript:void(0);" class="cache-index-refresh" data-content="/admin/cache/clearcache/8">演员排行榜列表(慢)</a></li>
                    </ul>
                </li>
            </ul>
HTML;
    }
}



