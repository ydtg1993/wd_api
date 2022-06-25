<?php


namespace App\Admin\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Models\CommConf;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;

class CommentUnderageController extends Controller
{
    public function index(Content $content)
    {
        return $content->title("首次登陆须知")
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form(new CommConf());
                    $form->action(admin_url('notes/store'));

                    $result = CommConf::getConfByType(CommConf::UNDERAGE);
                    $data = $result['values'];
                    $data = json_decode($data, true);

                    $form->editor("content", "内容")->default($data['content'] ?? "1");
                    $column->append((new Box("编辑", $form))->style('success'));

                });
            });
    }

    public function store()
    {
        $param = request()->input();
        $data = CommConf::where('type', CommConf::UNDERAGE)->get()->first();
        if (!$data) {
            $data = new CommConf();
            $data->type = CommConf::UNDERAGE;
        }
        $values = [
            'content' => $param['content'] ?? ""
        ];

        $data->values = json_encode($values);
        $data->save();

        admin_toastr('操作成功...', 'success');
        return response()->redirectTo('/admin/notes');

    }
}
