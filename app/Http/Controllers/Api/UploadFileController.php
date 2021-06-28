<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadFileController extends BaseController
{

    //文件上传

    public $configKey;

    public function upload(Request $request)
    {
        //上传文件最大大小,单位M
        $maxSize = 10;
        //支持的上传图片类型
        $allowed_extensions = ["png", "jpg", "gif"];

        $file = $request->file('file');

        //检查文件是否上传完成
        if ($file->isValid()){
            //检测图片类型
            $ext = $file->getClientOriginalExtension();
            if (!in_array(strtolower($ext),$allowed_extensions)){
                return $this->sendError("请上传".implode(",",$allowed_extensions)."格式的图片");
            }
            //检测图片大小
            if ($file->getSize() > $maxSize*1024*1024){
                return $this->sendError("图片大小限制".$maxSize."M");
            }
        }else{
            return $this->sendError($file->getErrorMessage());
        }
        $newFile = date('Y-m-d')."_".time()."_".uniqid().".".$file->getClientOriginalExtension();
        $disk = Storage::disk($this->configKey);
        $res = $disk->put($newFile,file_get_contents($file->getRealPath()));
        if($res){
            $url = substr($disk->getDriver()->getAdapter()->getPathPrefix(),strlen(base_path())).$newFile;
        }else{
            return $this->sendError($file->getErrorMessage());
        }
        return $this->sendJson($url);
    }


    /**
     * 上传头像
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePhoto(Request $request ){
        $this->configKey = 'upload_profile_files';
        return $this->upload( $request);
    }
}
