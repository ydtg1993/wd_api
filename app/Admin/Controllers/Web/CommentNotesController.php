<?php


namespace App\Admin\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Models\CommConf;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;

class CommentNotesController extends Controller
{
    public function index(Content $content)
    {
        return $content->title("短评须知")
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form(new CommConf());
                    $form->action(admin_url('notes/store'));

                    $result = CommConf::getConfByType(CommConf::NOTES);
                    $data = $result['values'];
                    $data = json_decode($data, true);

                    $form->radio('isopen', "开关")->options(['1' => "开", '2' => '关'])->default($data['isopen'] ?? "1");
                    $form->text('countdown', "显示时间(秒)")->default($data['countdown'] ?? "1");
                    $form->ckeditor("content", "内容")->default($data['content'] ?? "1");
                    $column->append((new Box("编辑", $form))->style('success'));

                });
            });
    }

    public function store()
    {
        $param = request()->input();
        $data = CommConf::where('type', CommConf::NOTES)->get()->first();
        if (!$data) {
            $data = new CommConf();
            $data->type = CommConf::NOTES;
        }
        $values = [
            'isopen' => $param['isopen'] ?? 1,
            'countdown' => $param['countdown'] ?? 1,
            'content' => $param['content'] ?? ""
        ];

        $data->values = json_encode($values);
        $data->save();

        admin_toastr('操作成功...', 'success');
        return response()->redirectTo('/admin/notes');

    }
}
