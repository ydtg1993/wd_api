<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::group(['namespace' => 'Api','middleware' => ['allow_origin','token']], function () {
//
//    Route::get('test', 'TestController@index');//测试
//
//});
//验证码API URL /captcha/api/math

Route::group(['namespace' => 'Api','middleware' => ['allow_origin','token']], function () {

    /***************************用户分块*****************************/
    Route::Post('user/changeUserInfo', 'UserController@changeUserInfo');
    Route::get('user/getUserInfo', 'UserController@getUserInfo');


    /***************************首页分块*****************************/
    Route::get('home', 'TestController@index');

    /*website setting*/
    Route::post('report', 'ReportController@saveReport');
    Route::post('announcement/getAnnouncement', 'AnnouncementController@getAnnouncement');

    //上传头像
    Route::post('file/uploadProfilePhoto', 'UploadFileController@uploadProfilePhoto');

});

//not login
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {

    Route::post('user/sendVerifyCode', 'UserController@sendVerifyCode')->middleware('verify_captcha');

    Route::post('user/login', 'UserController@login');
    Route::post('user/reg', 'UserController@register');

    Route::post('complaint', 'ComplaintController@saveComplaint');
    Route::get('conf/getAllConf', 'ConfController@getALlConf');
    Route::get('conf/getOneConf/{type}', 'ConfController@getOneConf');
});


//影片详情页
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('movie/detail', 'MovieDetailController@index');
    Route::post('movie/show', 'MovieDetailController@show');
    Route::post('movie/guess', 'MovieDetailController@guess');
    Route::post('movie/comment', 'MovieDetailController@comment');
});

Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('actor/detail', 'ActorDetailController@index');
    Route::post('actor/products', 'ActorDetailController@products');
});

Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('director/detail', 'DirectorDetailController@index');
    Route::post('director/products', 'DirectorDetailController@products');
});

Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('number/detail', 'NumberDetailController@index');
    Route::post('number/products', 'NumberDetailController@products');
});

Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('series/detail', 'SeriesDetailController@index');
    Route::post('series/products', 'SeriesDetailController@products');
});

Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('company/detail', 'FilmCompaniesDetailController@index');
    Route::post('company/products', 'FilmCompaniesDetailController@products');
});
