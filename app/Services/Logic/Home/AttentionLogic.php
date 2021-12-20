<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/12
 * Time: 15:38
 */

namespace App\Services\Logic\Home;


use App\Models\Movie;
use App\Models\MovieActorAss;
use App\Models\MovieDirectorAss;
use App\Models\MovieFilmCompaniesAss;
use App\Models\MovieLabel;
use App\Models\MovieLabelAss;
use App\Models\MovieNumber;
use App\Models\MovieNumberAss;
use App\Models\MovieSeriesAss;
use App\Models\PieceListMovie;
use App\Models\UserBrowseMovie;
use App\Models\UserLikeActor;
use App\Models\UserLikeDirector;
use App\Models\UserLikeFilmCompanies;
use App\Models\UserLikeNumber;
use App\Models\UserLikeSeries;
use App\Models\UserPieceList;
use App\Services\Logic\RedisCache;

class AttentionLogic extends HomeBaseLogic
{

    public function getData($arg = array())
    {
        //先判断是否登录- 登录继续
        $uid = $arg['uid'] ??0;
        if($uid <= 0)
        {
            $this->errorInfo->setCode(2000,'未登录！');
            return [];
        }

        $reData = RedisCache::getCacheData('movie','movie:attention:mid:list:',function () use ($arg,$uid)
        {
            $mids = [];
            //读取用户浏览的影片
            $userBrowseList = UserBrowseMovie::where('uid',$uid)->where('status',1)->limit(100)
                ->orderBy('browse_time','desc')
                ->get()
                ->pluck('mid')
                ->toArray();
            $likeBrowseListTemp = [];
            foreach ($userBrowseList as $val)
            {
                $likeBrowseListTemp[] = $val;
            };

            if(count($likeBrowseListTemp)>0)
            {
                //读演员
                $userBrowseActorList = MovieActorAss::whereIn('mid',$likeBrowseListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('aid')
                    ->toArray();

                $likeBrowseActorListTemp = [];
                foreach ($userBrowseActorList as $val)
                {
                    $likeBrowseActorListTemp[] = $val;
                };

                if(count($likeBrowseActorListTemp)>0)
                {
                    $userBrowseActorListMids = MovieActorAss::whereIn('aid',$likeBrowseActorListTemp)->where('status',1)->limit(100)
                        ->get()
                        ->pluck('mid')
                        ->toArray();

                    foreach ($userBrowseActorListMids as $val)
                    {
                        $mids[] = $val;
                    }
                }

                //读标签
                $userBrowseLabelList = MovieLabelAss::where('mid',$likeBrowseListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('cid')
                    ->toArray();

                $likeBrowseLabelListTemp = [];
                foreach ($userBrowseLabelList as $val)
                {
                    $likeBrowseLabelListTemp[] = $val;
                };

                if(count($likeBrowseLabelListTemp)>0)
                {
                    $userBrowseLabelListMids = MovieLabelAss::whereIn('cid',$likeBrowseLabelListTemp)->where('status',1)->limit(100)
                        ->get()
                        ->pluck('mid')
                        ->toArray();

                    foreach ($userBrowseLabelListMids as $val)
                    {
                        $mids[] = $val;
                    }
                }
            }

            //读取用户收藏演员
            $likeList = UserLikeActor::where('uid',$uid)->where('status',1)->limit(100)
                ->get()
                ->pluck('aid')
                ->toArray();

            $likeActorListTemp = [];
            foreach ($likeList as $val)
            {
                $likeActorListTemp[] = $val;
            };

            if(count($likeActorListTemp)>0)
            {
                //读演员关联的影片
                $tempIds = MovieActorAss::whereIn('aid',$likeActorListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }

            }

            //读取用户收藏导演
            $likeList =  UserLikeDirector::where('uid',$uid)->where('status',1)->limit(100)
                ->get()
                ->pluck('did')
                ->toArray();

            $likeDirectorListTemp = [];
            foreach ($likeList as $val)
            {
                $likeDirectorListTemp[] = $val;
            };

            if(count($likeDirectorListTemp)>0)
            {
                $tempIds = MovieDirectorAss::whereIn('did',$likeDirectorListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }
            }

            //读取用户收藏系列
            $likeList = UserLikeSeries::where('uid',$uid)->where('status',1)->limit(100)
                ->get()
                ->pluck('series_id')
                ->toArray();

            $likeSeriesListTemp = [];
            foreach ($likeList as $val)
            {
                $likeSeriesListTemp[] = $val;
            };

            if(count($likeSeriesListTemp)>0)
            {
                $tempIds = MovieSeriesAss::whereIn('series_id',$likeSeriesListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }
            }

            //读取用户收藏片商
            $likeList = UserLikeFilmCompanies::where('uid',$uid)->where('status',1)->limit(100)
                ->get()
                ->pluck('film_companies_id')
                ->toArray();

            $likeFilmCompaniesListTemp = [];
            foreach ($likeList as $val)
            {
                $likeFilmCompaniesListTemp[] = $val;
            };

            if(count($likeFilmCompaniesListTemp)>0)
            {
                $tempIds = MovieFilmCompaniesAss::whereIn('film_companies_id',$likeFilmCompaniesListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }
            }

            //读取用户收藏番号
            $likeList = UserLikeNumber::where('uid',$uid)->where('status',1)->limit(100)
                ->get()
                ->pluck('nid')
                ->toArray();
            $likeNumberListTemp = [];
            foreach ($likeList as $val)
            {
                $likeNumberListTemp[] = $val;
            };

            if(count($likeNumberListTemp)>0)
            {
                $tempIds = MovieNumberAss::whereIn('nid',$likeNumberListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }
            }

            //读取用户收藏片单
            $likeList = UserPieceList::where('uid',$uid)->where('status',1)->where('type',3)->limit(100)
                ->get()
                ->pluck('plid')
                ->toArray();

            $likePieceListTemp = [];
            foreach ($likeList as $val)
            {
                $likePieceListTemp[] = $val;
            };

            if(count($likePieceListTemp)>0)
            {
                $tempIds = PieceListMovie::whereIn('plid',$likePieceListTemp)->where('status',1)->limit(100)
                    ->get()
                    ->pluck('mid')
                    ->toArray();

                foreach ($tempIds as $val)
                {
                    $mids[] = $val;
                }
            }

            $mids = array_unique($mids);

            $reData = [];
            if(count($mids) <=  0)
            {
                //随机去
                $reData = self::randMid();
            }
            else
            {
                $tempMids = [];
                foreach ($mids as $val)
                {
                    $tempMids[] = $val;
                    if(count($tempMids) >= 100)
                    {
                        break;
                    }
                }
                $index = 0;
                foreach ($tempMids as $val)
                {
                    $reData[$index] = $val;
                    $index++;
                }

            }

            return $reData;
        },['uid'=>$uid],true);

        $page = $arg['page']??1;
        $pageSize = $arg['pageSize']??10;
        $pageLen = (($page-1)*$pageSize);
        $midsInfo = [];
        for ($i = $pageLen;$i<($pageLen+$pageSize);$i++)
        {
            if(($reData[$i]??0)<= 0)
            {
                break;
            }
            $midsInfo[] = $reData[$i]??0;
        }

        //读取影片信息
        $reDataList = RedisCache::getCacheData('movie','movie:attention:mid:list:',function () use ($midsInfo)
        {
            $reData = [];
            if(count($midsInfo) > 0 )
            {
                $MovieInfo = Movie::whereIn('id',$midsInfo)->get();
                foreach ($MovieInfo as $val)
                {
                    $reData[] = Movie::formatList($val);
                }
            }

            return $reData;
        },['uid'=>$uid,'mids'=>md5(json_encode($midsInfo))],true);

        return ['list'=>$reDataList,'sum'=>count($reData)];

    }

    /**
     * 随机取100条影片视频
     */
    public static function randMid()
    {
        $data = Movie::where('status',1)->where('is_up',1)
            ->inRandomOrder()
            ->take(100)
            ->get()
            ->pluck('id')
            ->toArray();
        $reData = [];
        $index = 0;
        foreach ($data as $val)
        {
            $reData[$index] = $val;
            $index++;
        }

        return $reData;
    }
}
