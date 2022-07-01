<?php
namespace App\Admin\Controllers\Api;

use App\Models\Movie;
use App\Models\MovieActor;
use App\Models\MovieDirector;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;

class ManageApiController extends AdminController
{
    public function getDirectors(Request $request)
    {
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

    public function searchActors(Request $request)
    {
        $q = $request->get('q');
        return MovieActor::where('name', 'like', "%$q%")->where(['status'=>1])->paginate(null, ['id', 'name as text']);
    }

    public function searchNumbers(Request $request)
    {
        $q = $request->get('q');
        return Movie::where('number', 'like', "%$q%")->where(['status'=>1,'is_up'=>1])->paginate(null, ['id', 'number as text']);
    }
}
