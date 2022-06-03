<?php

namespace App\Admin\Actions\Post;

use App\Models\MovieComment;
use App\Models\UserLock;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BatchLock extends BatchAction
{
    public $name = '批量封禁';

    public function handle(Collection $collection, Request $request)
    {
        $param = $request->input();
        foreach ($collection as $model) {
            // ...
            $user_lock = new UserLock();
            $user_lock->uid = $model->id;
            $user_lock->uname = $model->nickname;
            $user_lock->phone = $model->phone;
            $user_lock->email = $model->email;
            $user_lock->status = $param['type'];
            $user_lock->unlock_time = date("Y-m-d H:i:s", time() + 3600 * $param['unlock_time']);
            $user_lock->remarks = $param['remarks'] ?? "";
            $user_lock->save();
        }

        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $type = [
            2 => '禁言',
            3 => '拉黑',
        ];
        $this->radio('type', '类型')->options($type)->default(2);
        $this->select('unlock_time', '封禁时间')->options([
            '1' => '1天',
            '3' => '3天',
            '7' => '7天',
            '30' => '30天',
            '99' => '永久',
        ]);
        $this->textarea('remarks', '原因');
    }


}
