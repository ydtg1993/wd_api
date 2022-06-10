<?php


namespace App\Admin\Actions\Post;


use App\Models\ArticleComment;
use App\Models\UserClient;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class ArticleCommentHide extends RowAction
{
    public $name = '更改显示状态';

    public function handle(Model $model)
    {
        if ($model->status == 1){
            $model->status = 2;
        }else{
            $model->status = 1;
        }
        $model->save();
        return $this->response()->success('操作成功...')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定更改显示状态？');
    }
}
