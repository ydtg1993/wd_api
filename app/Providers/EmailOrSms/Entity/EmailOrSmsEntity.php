<?php


namespace App\Providers\EmailOrSms\Entity;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailOrSmsEntity extends Model
{
    protected $table = 'emailorsms_log';

    const UPDATED_AT = null;

    protected $fillable = [
        'emailorphone',
        'message',
        'type',
        'code',
        'uid',
        'ip',
        'timestamp',
        'created_at',
    ];
    protected $guarded = ['id'];


    public static function getUserPhoneInfo( $user ){
        return static::where('type',1)->where('uid',$user['uid'])->orderBy('id', 'desc')->select('code','timestamp')->first();
    }

    public static function getUserEmailInfo( $user ){
        return static::where('type',2)->where('uid',$user['uid'])->orderBy('id', 'desc')->select('code','timestamp')->first();
    }

    public static function createEmailOrSms( $data ){
        return static::create($data);
    }

    public static function getCodeByType($emailOrPhone,$type='phone' ){
        $mapType = [
            'phone'=>1,
            'email'=>2
        ];
        return static::where('type',$mapType[$type])->where('emailorphone',$emailOrPhone)->orderBy('id', 'desc')->select('code','timestamp')->first();
    }
}
