<?php

namespace App\Models;

use App\Services\Logic\Common;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table = 'movie';

    /**
     * ��ʽ��ӰƬ�б�����
     * @param array $data
     */
    public static function formatList($data = [])
    {
        $is_new_comment_day = ((strtotime($data['new_comment_time']??'') - strtotime(date('Y-m-d 00:00:00'))) >= 0)?1:2 ;//��������ʱ���ȥ ���տ�ʼʱ�� �������0 �����������
        $is_new_comment_day = ($is_new_comment_day == 2)?(
        (((strtotime($data['new_comment_time']??'') - (strtotime(date('Y-m-d 00:00:00')) -(60*60*24) )) >= 0)?3:2)
        ):1;

        $is_flux_linkage_day = ((strtotime($data['flux_linkage_time']??'') - strtotime(date('Y-m-d 00:00:00'))) >= 0)?1:2;
        $is_flux_linkage_day = ($is_flux_linkage_day == 2)?(
        (((strtotime($data['flux_linkage_time']??'') - (strtotime(date('Y-m-d 00:00:00')) -(60*60*24) )) >= 0)?3:2)
        ):1;

        $small_cover = $data['small_cover']??'';
        $big_cove = $data['big_cove']??'';
        $reData = [];
        $reData['id'] = $data['id']??0;

        $reData['name'] = $data['name']??'';
        $reData['number'] = $data['number']??'';
        $reData['release_time'] = $data['release_time']??'';
        $reData['created_at'] = $data['created_at']??'';

        $reData['is_download'] = $data['is_download']??1;//״̬ 1.��������  2.������
        $reData['is_subtitle'] = $data['is_subtitle']??1;//״̬ 1.������Ļ  2.����Ļ
        $reData['is_hot'] = $data['is_hot']??1;//״̬ 1.��ͨ  2.����
        $reData['is_new_comment'] = $is_new_comment_day;//״̬ 1.��������  2.��״̬ 3.��������

        $reData['is_flux_linkage'] = $is_flux_linkage_day;//״̬ 1.��������  2.��״̬ 3.��������
        $reData['comment_num'] = $data['comment_num']??0;
        $reData['score'] = $data['score']??0;
        $reData['small_cover'] = $small_cover == ''?'':(Common::getImgDomain().$small_cover);

        $reData['big_cove'] = $big_cove == ''?'':(Common::getImgDomain().$big_cove);

        return $reData;
    }
}
