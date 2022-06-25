<?php

namespace App\Admin\Controllers\Api;

use App\Models\Movie;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;

class ManageMovieSyncFileController extends AdminController
{
    /**
     * 单图上传
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function fileInput(Request $request)
    {
        $column = $request->input('column');
        $movie_id = $request->input('movie_id');
        $allowed_extensions = ["png", "jpg", "jpeg", "gif", "mp4", "mpg", "mpeg", "avi", "rmvb", "image/gif", "image/jpeg", "image/png", "video/mp4", "video/mpeg", "video/x-msvideo", "audio/x-pn-realaudio"];
        $file = $request->file($column);

        $mm = $file->getMimeType();
        //检查文件是否上传完成
        if (!in_array($mm, $allowed_extensions)) {
            return '文件格式错误';
        }

        $movie = Movie::where('id', $movie_id)->first();
        $before_file = $movie->{$column};
        $base_dir = rtrim(public_path('resources'), '/') . '/';
        if (is_file($base_dir . $before_file)) {
            unlink($base_dir . $before_file);
        }

        $newDir = $base_dir . 'manage_movie/' . ($movie->id % 512) . '/' . $movie->id . '/';
        if (!is_dir($newDir)) {
            mkdir($newDir, 0777, true);
            chmod($newDir, 0777);
        }
        $newFile = substr(md5($movie->number), 0, 6) . '_' . $column . "." . $file->getClientOriginalExtension();
        $res = move_uploaded_file($file->getPathname(), $newDir . '/' . $newFile);
        if (!$res) {
            return '文件存储失败';
        }
        Movie::where('id', $movie->id)->update([$column => 'manage_movie/' . $movie->id . '/' . $newFile]);
        return response()->json([], 200);
    }

    /**
     * 组图批量
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function map(Request $request)
    {
        $movie_id = $request->input('movie_id');
        $allowed_extensions = ["png", "jpg", "jpeg", "gif", "mpg", "mpeg", "image/gif", "image/jpeg", "image/png", "video/mpeg"];
        $file = $request->file('map');
        $mm = $file->getMimeType();

        //检查文件是否上传完成
        if (!in_array($mm, $allowed_extensions)) {
            return '文件格式错误';
        }

        $movie = Movie::where('id', $movie_id)->first();
        $base_dir = rtrim(public_path('resources'), '/') . '/';

        $newDir = $base_dir . 'manage_movie/' . ($movie->id % 512) . '/' . $movie->id . '/map/';
        if (!is_dir($newDir)) {
            mkdir($newDir, 0777, true);
            chmod($newDir, 0777);
        }
        $newFile = substr(md5($file->getPathname() . time()), 0, 6) . "." . $file->getClientOriginalExtension();
        $res = move_uploaded_file($file->getPathname(), $newDir . '/' . $newFile);
        $tempImgPath = 'manage_movie/' . $movie->id . '/map/' . $newFile;
        if (!$res) {
            return '文件存储失败';
        }
        $map = (array)json_decode($movie->map);
        $map[] = ['big_img' => $tempImgPath, 'img' => $tempImgPath];
        Movie::where('id', $movie_id)->update(['map' => json_encode($map)]);
        return response()->json([], 200);
    }

    /**
     * @param Request $request
     */
    public function fileRemove(Request $request)
    {
        $movie_id = $request->input('movie_id');
        $column = $request->input('column');
        $key = $request->input('key');

        $movie = Movie::where('id',$movie_id)->first();
        $map = (array)json_decode($movie->map,true);

        $base_dir = rtrim(public_path('resources'), '/') . '/';
        foreach ($map as $k=>$m)
        {
            if($key == $k){
                if(is_file($base_dir.$m['big_img'])) {
                    unlink($base_dir . $m['big_img']);
                }
                if(is_file($base_dir.$m['img'])) {
                    unlink($base_dir . $m['img']);
                }
                array_splice($map,$k,1);
                break;
            }
        }

        Movie::where('id', $movie_id)->update(['map' => $map]);
        return response()->json([]);
    }
}
