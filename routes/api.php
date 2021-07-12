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
    Route::get('user/getHomeUser', 'UserActionController@getHomeUser');//用户个人主页信息
    Route::get('user/getHomeUserAction', 'UserActionController@getHomeUserAction');//用户个人主页动作信息

    Route::get('user/add/action', 'UserActionController@add');//添加用户动作 各种收藏 记录 关注等
    Route::get('user/get/action/list', 'UserActionController@getList');//获取用户动作列表 各种收藏 记录 关注等
    Route::post('user/piece/list/add/movie', 'PieceListController@addMovie');//给片单添加或者移除一个影片


    /*website setting*/
    Route::post('report', 'ReportController@saveReport')->middleware('verify_captcha');

    //上传头像
    Route::post('file/uploadProfilePhoto', 'UploadFileController@uploadProfilePhoto');
    Route::post('file/upload/piece/list', 'UploadFileController@uploadPieceList');//上传片单图片

    //消息通知
    Route::get('notify/getNotifyList', 'NotifyController@getNotifyList');
    Route::post('notify/setRead', 'NotifyController@setRead');
    Route::post('notify/delete', 'NotifyController@delete');
    //赞踩
    Route::post('comment/action', 'MovieDetailController@action');
});

//not login
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {

    Route::post('user/sendVerifyCode', 'UserController@sendVerifyCode')->middleware('verify_captcha');

    Route::post('user/login', 'UserController@login');
    Route::post('user/reg', 'UserController@register');
    Route::post('user/forgetPassword', 'UserController@forgetPassword');

    Route::post('complaint', 'ComplaintController@saveComplaint');
    Route::get('conf/getAllConf', 'ConfController@getALlConf');
    Route::get('conf/getOneConf/{type}', 'ConfController@getOneConf');
    Route::post('announcement/getAnnouncement', 'AnnouncementController@getAnnouncement');

    //统计相关
    Route::post('count/movie', 'UserActionController@addCountMovie');
    Route::post('count/actor', 'UserActionController@addCountActor');
});


Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::post('movie/search', 'MovieController@search');
});

//详情页
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('movie/detail', 'MovieDetailController@index');
    Route::post('movie/show', 'MovieDetailController@show');
    Route::post('movie/guess', 'MovieDetailController@guess');
    Route::post('movie/comment', 'MovieDetailController@comment');
    Route::post('movie/reply', 'MovieDetailController@reply');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('actor/detail', 'ActorDetailController@index');
    Route::post('actor/products', 'ActorDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('director/detail', 'DirectorDetailController@index');
    Route::post('director/products', 'DirectorDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('number/detail', 'NumberDetailController@index');
    Route::post('number/products', 'NumberDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('series/detail', 'SeriesDetailController@index');
    Route::post('series/products', 'SeriesDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::post('company/detail', 'FilmCompaniesDetailController@index');
    Route::post('company/products', 'FilmCompaniesDetailController@products');
});

//登录 未登录双用
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {

    /***************************片单分块*****************************/
    Route::get('piece/list', 'PieceListController@getPieceList');//片单列表
    Route::get('piece/info', 'PieceListController@getInfo');//片单详情
    Route::get('piece/movie/list', 'PieceListController@getMovieList');//片单影片列表

    /***************************影片属性分块*****************************/
    Route::get('movie/attributes/actor/list', 'MovieAttributesController@getActorList');//获取演员列表
    Route::get('movie/attributes/series/list', 'MovieAttributesController@getSeriesList');//获取系列列表
    Route::get('movie/attributes/film/companies/list', 'MovieAttributesController@getFilmCompaniesList');//获取片商列表

    /***************************首页分块*****************************/
    Route::get('home', 'HomeController@index');
    Route::get('rank', 'HomeController@rank');
    Route::get('actor/rank', 'HomeController@actorRank');
    Route::get('search', 'HomeController@search');
    Route::get('search/log', 'HomeController@searchLog');


});
