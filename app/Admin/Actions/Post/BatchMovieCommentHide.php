<?php


namespace App\Admin\Actions\Post;


use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchMovieCommentHide extends BatchAction
{
    public $name = '批量隐藏';

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            $model->status = 2;
            $model->save();
        }

        return $this->response()->success('操作成功...')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定隐藏吗？');
    }
}
