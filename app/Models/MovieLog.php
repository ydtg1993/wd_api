<?php

namespace App\Models;

use App\Services\Logic\RedisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MovieLog extends Model
{
    protected $table = 'movie_log';

    const UPDATED_AT = null;

    /**
     * 添加影片浏览记录
     */
    public static function addMovieBrowse($mid)
    {
        if ($mid <= 0) {
            return 0;
        }

        //查询影片类别
        $categoryAssociate = MovieCategoryAssociate::where('mid', $mid)->where('status', 1)->first();
        $movieObj = new MovieLog();
        $movieObj->mid = $mid;
        $movieObj->cid = $categoryAssociate['cid'] ?? 0;
        $movieObj->save();
        return $movieObj->id;
    }

    public static function getRankingVersion($type, $time)
    {
        $reData = ['list' => [], 'sum' => 0];
        $t = time();
        $M = MovieLog::where('cid', $type);
        switch ($time) {
            case 1:
                $today = date('Y-m-d 00:00:00', $t);
                $yesterday = date('Y-m-d 00:00:00', strtotime("-1 days", $t));
                $M = $M->whereBetween('created_at', [$yesterday, $today]);
                break;
            case 2:
                $M = $M->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('this week', $t)));
                break;
            case 3:
                $M = $M->where('created_at', '>=', date('Y-m-01 00:00:00', $t));
                break;
        }
        $M = $M->selectRaw('count(mid) as num,mid')->groupBy('mid');
        $browseList = $M->orderBy('num', 'desc')->offset(0)->limit(100)->get()->pluck('mid')->all();

        if (count($browseList) < 100) {
            $M2 = MovieLog::where('cid', $type);
            $M2 = $M2->whereNotIn('id',$browseList);
            $rest = 100 - count($browseList);
            $t = $t - 86400 * 30;
            switch ($time) {
                case 1:
                    $today = date('Y-m-d 00:00:00', $t);
                    $yesterday = date('Y-m-d 00:00:00', strtotime("-3 days", $t));
                    $M2 = $M2->whereBetween('created_at', [$yesterday, $today]);
                    break;
                case 2:
                    $M2 = $M2->whereBetween('created_at', [
                        date('Y-m-d 00:00:00', strtotime('this week', $t)),
                        date('Y-m-d 00:00:00', strtotime('next week', $t))
                    ]);
                    break;
                case 3:
                    $M2 = $M2->where('created_at', '>=', date('Y-m-01 00:00:00', $t));
                    break;
            }
            $M2 = $M2->selectRaw('count(mid) as num,mid')->groupBy('mid');
            $reData['sum'] += $M2->offset(0)->limit($rest)->get()->count();
            $browseList += $M2->orderBy('num', 'desc')->offset(0)->limit($rest)->get()->pluck('mid')->toArray();
        }
        $MovieList = Movie::whereIn('id', $browseList)->get();
        $tempMovie = [];
        foreach ($MovieList as $val) {
            $data = Movie::formatList($val);//格式化视频数据
            $data['score_people'] = $val['score_people'];
            $data['wan_see'] = $val['wan_see'];
            $data['seen'] = $val['seen'];
            $data['pv'] = DB::table('movie_log')->where('mid', $val['id'])->count();
            $tempMovie[$val['id'] ?? 0] = $data;
        }
        $rank = 0;
        foreach ($browseList as $val) {
            $rank++;
            $temp = $tempMovie[$val] ?? [];
            $temp['rank'] = $rank;
            $reData['list'][] = $temp;
        }
        $reData['sum'] = count($reData['list']);
        return $reData;
    }
}
