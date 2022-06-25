<?php
namespace App\Admin\Controllers\Api;

use App\Models\MovieDirector;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;

class DirectorController extends AdminController
{
    public function get(Request $request)
    {
        $q = $request->get('q');
        $records= MovieDirector::where('status',1)->select('id','name')->get();
        $data = [];
        foreach ($records as $record){
            $data[] = [
                'id'=>$record->id,
                'text'=>$record->name
            ];
        }
        return response()->json($data);
    }
}
