<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Form;

class CommonController extends Controller
{
    public static function disableDetailConf(&$form)
    {
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
    }

    public static function upload($file, $model,$id,$column='photo',$dirname='',$chunk=10)
    {
        $allowed_extensions = ["png", "jpg", "jpeg", "gif", "mpg", "mpeg", "image/gif", "image/jpeg", "image/png", "video/mpeg"];
        $mm = $file->getMimeType();

        //检查文件是否上传完成
        if (!in_array($mm, $allowed_extensions)) {
            throw new \Exception('文件格式错误');
        }
        $base_dir = rtrim(public_path('resources'), '/') . '/';

        $record = $model::where('id', $id)->first();
        $chunk_dir = ($id % $chunk) .'/'.$id;
        if ($record->{$column}) {
            $old_file = $base_dir . $record->{$column};
            if (is_file($old_file)) {
                unlink($old_file);
            }
        }
        $newDir = $base_dir . $dirname.'/' . $chunk_dir . '/';
        if (!is_dir($newDir)) {
            mkdir($newDir, 0777, true);
            chmod($newDir, 0777);
        }
        $newFile = substr(md5($file->getPathname()), 0, 6) . "." . $file->getClientOriginalExtension();
        $res = move_uploaded_file($file->getPathname(), $newDir . $newFile);
        if (!$res) {
            throw new \Exception('文件存储失败');
        }

        return $dirname.'/' . $chunk_dir . '/' . $newFile;
    }
}
