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
//    Route::any('test', 'TestController@index');//测试
//
//});
//验证码API URL /captcha/api/math

Route::group(['namespace' => 'Api','middleware' => ['allow_origin','token']], function () {

    /***************************用户分块*****************************/
    Route::any('user/changeUserInfo', 'UserController@changeUserInfo');
    Route::any('user/getUserInfo', 'UserController@getUserInfo');


    Route::any('user/add/action', 'UserActionController@add');//添加用户动作 各种收藏 记录 关注等
    Route::any('user/get/action/list', 'UserActionController@getList');//获取用户动作列表 各种收藏 记录 关注等
    Route::any('user/piece/list/add/movie', 'PieceListController@addMovie');//给片单添加或者移除一个影片
    Route::any('user/give_score', 'UserActionController@giveScore');

    //想看
    Route::any('user/wantsee/add', 'WantseeController@add');  //想看添加
    Route::any('user/wantsee/del', 'WantseeController@del');  //想看删除

    //看过
    Route::any('user/seen/add', 'SeenController@add');  //看过添加
    Route::any('user/seen/get', 'SeenController@info');  //看过添加
    Route::any('user/seen/edit', 'SeenController@edit'); //看过修改
    Route::any('user/seen/del', 'SeenController@del');  //看过删除

    /*website setting*/
    Route::any('report', 'ReportController@saveReport')->middleware('verify_captcha');

    //上传头像
    Route::any('file/uploadProfilePhoto', 'UploadFileController@uploadProfilePhoto');
    Route::any('file/upload/piece/list', 'UploadFileController@uploadPieceList');//上传片单图片

    //消息通知
    Route::any('notify/getNotifyList', 'NotifyController@getNotifyList');
    Route::any('notify/setRead', 'NotifyController@setRead');
    Route::any('notify/delete', 'NotifyController@delete');
    //赞踩
    Route::any('comment/action', 'MovieDetailController@action');
});

//not login
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {

    Route::any('user/sendVerifyCode', 'UserController@sendVerifyCode')->middleware('verify_captcha');

    Route::any('user/login', 'UserController@login')->middleware('verify_captcha');
    Route::any('user/reg', 'UserController@register');
    Route::any('user/forgetPassword', 'UserController@forgetPassword');
    Route::any('user/loginwithcode', 'UserController@loginWithVerifyCode');

    Route::any('complaint', 'ComplaintController@saveComplaint');
    Route::any('conf/getAllConf', 'ConfController@getALlConf');
    Route::any('conf/getOneConf/{type}', 'ConfController@getOneConf');
    Route::any('announcement/getAnnouncement', 'AnnouncementController@getAnnouncement');

    //统计相关
    Route::any('count/movie', 'UserActionController@addCountMovie');
    Route::any('count/actor', 'UserActionController@addCountActor');
    //验证码
    Route::any('captcha/cors/{config?}', '\Mews\Captcha\CaptchaController@getCaptchaApi');
    //基于ES搜索（作废）
    Route::any('es/search', '\Es\EsVideoController@search');

    //获取设置的域名列表
    Route::any('conf/domain', 'DomainController@getDomain');
    //获取广告列表
    Route::any('ads/list', 'AdsController@getList');
});

//not login
Route::group(['namespace' => 'Api\Es','middleware' => ['allow_origin']], function () {
    //基于ES搜索(作废)
    Route::any('es/search', 'EsVideoController@search');
});

//no login(作废)
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {
    Route::any('movie/search', 'MovieController@search');
});

//not login 搜索引擎
Route::group(['namespace' => 'Api','middleware' => ['allow_origin']], function () {

    //搜索
    Route::any('search', 'SearchController@search');

    //热门关键词
    Route::any('search/hotword', 'SearchController@hotword');
});

//详情页
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('movie/detail', 'MovieDetailController@index');
    Route::any('movie/show', 'MovieDetailController@show');
    Route::any('movie/guess', 'MovieDetailController@guess');
    Route::any('movie/comment', 'MovieDetailController@comment');
    Route::any('movie/reply', 'MovieDetailController@reply')->middleware('verify_captcha');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('actor/detail', 'ActorDetailController@index');
    Route::any('actor/products', 'ActorDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('director/detail', 'DirectorDetailController@index');
    Route::any('director/products', 'DirectorDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('number/detail', 'NumberDetailController@index');
    Route::any('number/products', 'NumberDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('series/detail', 'SeriesDetailController@index');
    Route::any('series/products', 'SeriesDetailController@products');
});
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {
    Route::any('company/detail', 'FilmCompaniesDetailController@index');
    Route::any('company/products', 'FilmCompaniesDetailController@products');
});

//登录 未登录双用
Route::group(['namespace' => 'Api','middleware' => ['allow_origin','tokens']], function () {

    /***************************片单分块*****************************/
    Route::any('piece/list', 'PieceListController@getPieceList');//片单列表
    Route::any('piece/info', 'PieceListController@getInfo');//片单详情
    Route::any('piece/movie/list', 'PieceListController@getMovieList');//片单影片列表

    /***************************影片属性分块*****************************/
    Route::any('movie/attributes/actor/list', 'MovieAttributesController@getActorList');//获取演员列表
    Route::any('movie/attributes/series/list', 'MovieAttributesController@getSeriesList');//获取系列列表
    Route::any('movie/attributes/film/companies/list', 'MovieAttributesController@getFilmCompaniesList');//获取片商列表

    /***************************首页分块*****************************/
    Route::any('home', 'HomeController@index');
    Route::any('rank', 'HomeController@rank');
    Route::any('actor/rank', 'HomeController@actorRank');
    //Route::any('search', 'HomeController@search');
    Route::any('search/log', 'HomeController@searchLog');
    Route::any('search/log/clear', 'HomeController@searchLogClear');

    Route::any('user/getHomeUser', 'UserActionController@getHomeUser');//其他用户个人主页信息
    Route::any('user/getHomeUserAction', 'UserActionController@getHomeUserAction');//其他用户个人主页动作信息

   //灌水专用
   Route::any('/nologin/batch_hand_send', 'SeenController@batch');

});
