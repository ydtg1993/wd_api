<?php

namespace App\Admin\Actions\Post;

use App\Models\UserClient;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class UnLock extends RowAction
{
    public $name = '解封';

    public function handle(Model $model)
    {
        $model->status = 1;
        $model->unlock_time = date("Y-m-d H:i:s");
        $model->save();

        $user = UserClient::where("id", $model->uid)->first();
        $user->status = 1;
        $user->save();

        return $this->response()->success('操作成功...')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定解封？');
    }
}
