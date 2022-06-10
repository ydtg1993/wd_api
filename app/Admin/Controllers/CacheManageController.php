<?php


namespace App\Admin\Controllers;


use App\Console\Commands\RankList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CacheManageController extends Controller
{
    public function clearCache($type, Request $request)
    {

        if ($request->method() == 'POST') {

            switch ($type) {
                case 0:
                    $this->clearAll('home:*');
                    break;
                case 1:
                    $this->clearAll('actor_detail_products:*');
                    break;
                case 2:
                    $this->clearAll('series_detail_products:*');
                    break;
                case 3:
                    $this->clearAll('film_company_detail_products:*');
                    break;
                case 4:
                    $this->clearAll('number_detail_products:*');
                    break;
                case 5:
                    $this->clearAll('movie:lists:catecory:*');
                    $this->clearAll('movie:count:catecory:*');
                    break;
                case 6:
                    (new RankList())->movie();
                    break;
                case 7:
                    $this->clearAll('Conf:*');
                    break;
                case 8:
                    (new RankList())->actor();
                    break;
                default:
                    return response()->json([
                        'code' => 404,
                        'msg' => '找不到此类型...'
                    ]);
            }

            return response()->json([
                'code' => 200,
                'msg' => '缓存清除成功'
            ]);
        }

        return response()->json([
            'code' => 500,
            'msg' => '什么都没做....'
        ]);
    }

    public function clearAll($cache)
    {
        $prefix = config('database.redis.options.prefix');
        $keys = Redis::keys($cache);
        foreach ($keys as $key) {
            Redis::del(str_replace($prefix, '', $key));
        }
    }
}
