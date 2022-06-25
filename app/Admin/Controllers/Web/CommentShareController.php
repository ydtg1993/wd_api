<?php


namespace App\Admin\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Models\CommConf;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;

class CommentShareController extends Controller
{
    public function index(Content $content)
    {
        return $content->title("APP分享")
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form(new CommConf());
                    $form->action(admin_url('share/store'));

                    $result = CommConf::getConfByType(CommConf::SHARE);
                    $data = $result['values'];
                    $data = json_decode($data, true);

//                    $form->content = $data['content'];
                    $form->ckeditor("content", "内容")->default($data['content'] ?? "1");


                    $column->append((new Box("编辑", $form))->style('success'));

                });
            });
    }

    public function store()
    {
        $param = request()->input();
        $data = CommConf::where('type', CommConf::SHARE)->get()->first();
        if (!$data) {
            $data = new CommConf();
            $data->type = CommConf::SHARE;
        }
        $values = [
            'content' => $param['content'] ?? ""
        ];

        $data->values = json_encode($values);
        $data->save();

        admin_toastr('操作成功...', 'success');
        return response()->redirectTo('/admin/share');

    }
}
