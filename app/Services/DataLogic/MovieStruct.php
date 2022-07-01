<?php


namespace App\Services\DataLogic;


use App\Models\Movie;
use Illuminate\Support\Facades\Log;

class MovieStruct extends DLStruct
{
    public $struct = [
        'id' => '',
        'number' => '',
        'name' => '',
        'cid' => 0,
        'time' => 0,
        'release_time' => '',
        'small_cover' => '',
        'big_cove' => '',
        'trailer' => '',
        'is_download' => '',
        'is_subtitle' => '',
        'is_hot' => '',
        'is_short_comment' => '',
        'is_up' => 2,
        'score' => 0,
        'score_people' => 0,
        'wan_see'=>0,
        'seen'=>0,
        'comment_num' => 0,
        'flux_linkage_num' => 0,
        'new_comment_time' => '',
        'flux_linkage_time' => '',
        'updated_at' => '',
        'created_at' => '',
        'weight' => 0
    ];

    public function first($id)
    {
        $data = $this->struct;
        try {
            $check = $this->ES->exists([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $id,
            ]);
            if (!$check) return $data;

            $record = $this->ES->get([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $id
            ]);
            if ($record['found'] == true) {
                $data = $this->fillStruct($record['_source']);
            } else {
                $movie = Movie::where('id', $id)->first();
                if (!$movie) return $data;
                $movie = $this->fillStruct($movie->toArray());
                //补增
                try {
                    $this->store($movie);
                } catch (\Exception $e) {
                    Log::channel('elastic')->
                    emergency("补增es", ['movie' => $movie, 'message' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('elastic')->
            emergency("删除es", ['id' => $id, 'message' => $e->getMessage()]);
            return $data;
        }
        return $data;
    }

    public function get($ids)
    {
        $records = $this->ES->mget([
            'index' => 'movie',
            'type' => '_doc',
            'body' => array('ids' => $ids)
        ]);
        $data = [];
        if (!isset($records['docs'])) return;
        foreach ($records['docs'] as $record) {
            if ($record['found'] == true) {
                $data[] = $this->fillStruct($record['_source']);
                continue;
            }
            $id = (int)$record['_id'];
            $movie = Movie::where('id', $id)->first();
            if (!$movie) continue;
            $movie = $this->fillStruct($movie->toArray());
            $data[] = $movie;
            //补增
            try {
                $this->store($movie);
            } catch (\Exception $e) {
                Log::channel('elastic')->
                emergency("补增es", ['movie' => $movie, 'message' => $e->getMessage()]);
            }
        }
        return $data;
    }

    public function store($params)
    {
        if (!$this->checkStruct($params)) {
            return false;
        }
        $params['pv'] = 0;
        try {
            $this->ES->index([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $params['id'],
                'body' => $params
            ]);
        } catch (\Exception $e) {
            Log::channel('elastic')->
            emergency("新增es", ['params' => $params, 'message' => $e->getMessage()]);
            return false;
        }
        return true;
    }

    public function update($id, $params)
    {
        $check = $this->ES->exists([
            'index' => 'movie',
            'type' => '_doc',
            'id' => $params['id'],
        ]);
        if (!$check) return false;
        try {
            $this->ES->update([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $id,
                'body' => [
                    'doc' => $params
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('elastic')->
            emergency("修改es", ['params' => $params, 'message' => $e->getMessage()]);
            return false;
        }
        return true;
    }

    public function delete($id)
    {
        try {
            $check = $this->ES->exists([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $id,
            ]);
            if (!$check) return false;
            $this->ES->delete([
                'index' => 'movie',
                'type' => '_doc',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            Log::channel('elastic')->
            emergency("删除es", ['id' => $id, 'message' => $e->getMessage()]);
            return false;
        }
        return true;
    }
}
