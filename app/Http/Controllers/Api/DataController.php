<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/14
 * Time: 17:45
 */

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{

    public function getData(Request $request)
    {
        $data  = $request->input();
        $pageSize = $data['pageSize']??100;
        $page = $data['page']??1;
        $page = ($page-1)*$pageSize;
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->where('ctime','>=',$beginTime)->skip((int)$page)->take((int)$pageSize)->orderBy('ctime', 'asc')->get();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    public function getDataCount(Request $request)
    {
        $data  = $request->input();
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->where('ctime','>=',$beginTime)->count();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    public function getDataFluxLinkage(Request $request)
    {
        $data  = $request->input();
        $pageSize = $data['pageSize']??100;
        $page = $data['page']??1;
        $page = ($page-1)*$pageSize;
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->where('utime','>=',$beginTime)->skip((int)$page)->take((int)$pageSize)->orderBy('utime', 'asc')->get();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    public function getDataFluxLinkageCount(Request $request)
    {
        $data  = $request->input();
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->where('utime','>=',$beginTime)->count();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    public function getActorData(Request $request)
    {
        $data  = $request->input();
        $pageSize = $data['pageSize']??100;
        $page = $data['page']??1;
        $page = ($page-1)*$pageSize;
        $tableName = $data['tableName']??'javdb_actor';
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->skip((int)$page)->take((int)$pageSize)->get();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    public function getActorDataCount(Request $request)
    {
        $data  = $request->input();
        $tableName = $data['tableName']??'javdb_actor';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));
        /*秘钥校验后面补*/

        $data = DB::connection('mongodb')->collection($tableName)->count();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    /**
     * 获取数据用于更新
     * 第一次更新全部数据遍历一次
     *
     * 判断 utime 大于 72 小时的数据 ，更新本地 mysql 数据库
     *
     * 同时更新 update_time ?
     *
     * 接下来的步骤：
     * 其它脚本根据 updated_at 将 orginail 里的数据更新到 其它表 ?
     * 将 colle_ * 表里的数据更新到最终表 ？
     */
    public function getDataForUpdate(Request $request)
    {
        $data  = $request->input();
        $pageSize = $data['pageSize']??100;
        $page = $data['page']??1;
        $page = ($page-1)*$pageSize;
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));

        $data = DB::connection('mongodb')->collection($tableName)->where('utime','>=',$beginTime)->skip((int)$page)->take((int)$pageSize)->orderBy('utime', 'asc')->get();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }

    /**
     * 获取
     * @param Request $request
     * @return JsonResponse
     */
    public function getDataCountForUpdate(Request $request)
    {
        $data  = $request->input();
        $tableName = $data['tableName']??'javdb';
        $beginTime = $data['beginTime']??(date('Y-m-d 00:00:00',time()));


        $data = DB::connection('mongodb')->collection($tableName)->where('utime','>=',$beginTime)->count();
        return response()->json(['code'=>200,'data'=>$data,'msg'=>'ok'],200);
    }



}
