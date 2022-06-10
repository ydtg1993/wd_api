<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Form;

/**
 * Class ComponentViewer
 * @package App\Admin\Controllers
 */
class FileInput
{
    public static function files(Form $form,$column,$title,$resources=[],$options=[])
    {
        $file_input_settings = [
            'language'=> 'zh',
            'showClose' => false,
            'showPreview' => true,
            'uploadAsync' =>true,
            'autoReplace'=>false,
            'showUpload'=>false,
            'dropZoneEnable'=>true,
            'initialPreviewAsData'=>true,
            'showRemove'=>false,
            'overwriteInitial'=>false
        ];
        $settings = array_merge($file_input_settings,$options);
        foreach ($resources as $key=>$resource){
            $settings['initialPreview'][] = config('app.url').'/resources/'.$resource;
            $settings['initialPreviewConfig'][] = ['caption'=>substr(basename($resource),0,8),'key'=>$key];
        }
        $settings = json_encode($settings);

        $form->html(<<<EOF
<input id="{$column}" name="{$column}" type="file">
<script>
new Promise((resolve, reject) => {
        while (true){
        if(document.getElementById('{$column}') instanceof HTMLElement){
            return resolve();
        }
    }}).then(function() {
        $('#{$column}').fileinput(JSON.parse('{$settings}')).on('filebeforedelete', function () {
            var aborted = !window.confirm('确定删除该文件么?');
            return aborted;
        }).on('filedeleted', function (event, data) {
            console.log(data)
        }).on("filebatchselected", function (event, files) {
            $('#{$column}').fileinput("upload");
        }).on('fileerror', function (event, data, msg) {
            console.log(data.id);
            console.log(data.index);
            console.log(data.file);
            console.log(data.reader);
            console.log(data.files);
            // 获取信息
            alert(msg);
        });
});
</script>
EOF
            ,$title);

    }
}
