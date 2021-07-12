<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/6/16
 * Time: 9:39
 */

namespace App\Http\Controllers\Api;

use App\Models\MovieActor;
use App\Models\MovieFilmCompanies;
use App\Models\MoviePieceList;
use App\Models\MovieSeries;
use App\Models\UserLikeUser;
use App\Services\Logic\Common;
use App\Services\Logic\RedisCache;
use App\Services\Logic\User\Notes\NotesLogic;
use App\Services\Logic\User\Notes\PieceListLogic;
use App\Services\Logic\User\UserInfoLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class MovieAttributesController extends BaseController
{
    /**
     * 获取演员列表
     * @param Request $request
     */
    public function getActorList(Request $request)
    {
        $template = ['cid'=>0];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }

        $template['page'] = 1;
        $template['pageSize'] = 10;
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $dataListObj = new MovieActor();
        $reData = [];
        $reData['page'] = $data['page']??1;
        $reData['pageSize'] = $data['pageSize']??10;
        $dataList = $dataListObj->getList($data);
        $reData['actorList'] = $dataList['list']??[];
        $reData['sum'] = $dataList['sum']??0;
        return $this->sendJson($reData);

    }

    /**
     * 获取系列列表
     * @param Request $request
     */
    public function getSeriesList(Request $request)
    {
        $template = ['cid'=>0];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }

        $template['page'] = 1;
        $template['pageSize'] = 10;
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $dataListObj = new MovieSeries();
        $reData = [];
        $reData['page'] = $data['page']??1;
        $reData['pageSize'] = $data['pageSize']??10;
        $dataList = $dataListObj->getList($data);
        $reData['seriesList'] = $dataList['list']??[];
        $reData['sum'] = $dataList['sum']??0;
        return $this->sendJson($reData);

    }

    /**
     * 获取片商列表
     * @param Request $request
     */
    public function getFilmCompaniesList(Request $request)
    {
        $template = ['cid'=>0];
        $data = $request->all();
        if(!$this->haveToParam($template,$data))
        {
            return $this->sendJson('',202);
        }

        $template['page'] = 1;
        $template['pageSize'] = 10;
        $data = $this->paramFilter($template,$data);
        if($data  === false)
        {
            return $this->sendJson('',201);
        }

        $dataListObj = new MovieFilmCompanies();
        $reData = [];
        $reData['page'] = $data['page']??1;
        $reData['pageSize'] = $data['pageSize']??10;
        $dataList = $dataListObj->getList($data);
        $reData['filmCompaniesList'] = $dataList['list']??[];
        $reData['sum'] = $dataList['sum']??0;
        return $this->sendJson($reData);

    }
}