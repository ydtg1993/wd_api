<?php

namespace App\Admin\Actions\Post;

use App\Models\MovieComment;
use App\Models\UserLock;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchLock extends BatchAction
{
    public $name = '批量封禁';

    public function handle(Collection $collection, Request $request)
    {
        $param = $request->input();

        $unlocktime = date("Y-m-d",strtotime("+1 day"));
        switch($param['unlock_time'])
        {
            case 1:
                $unlocktime = date("Y-m-d",strtotime("+1 day"));    //封一天
                break;
            case 3:
                $unlocktime = date("Y-m-d",strtotime("+3 day"));    //封三天
                break;
            case 7:
                $unlocktime = date("Y-m-d",strtotime("+1 week"));   //封一周
                break;
            case 30:
                $unlocktime = date("Y-m-d",strtotime("+1 month"));  //封一个月
                break;
            case 99999:
                $unlocktime = date("Y-m-d",strtotime("+100 year"));    //永久封闭
                break;
        }

        foreach ($collection as $model) {
            $model->status = $param['type'];
            $model->save();

            $user_lock = UserLock::where('uid', $model->id)->first();
            $user_lock = $user_lock ?? new UserLock();

            $user_lock->uid = $model->id;
            $user_lock->uname = $model->nickname;
            $user_lock->phone = $model->phone;
            $user_lock->email = $model->email;
            $user_lock->status = $param['type'];
            $user_lock->unlock_time = $unlocktime;
            $user_lock->remarks = $param['remarks'] ?? "";
            $user_lock->save();

        }

        return $this->response()->success('操作成功...')->refresh();
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
            '99999' => '永久',
        ]);
        $this->textarea('remarks', '原因');
    }


}
