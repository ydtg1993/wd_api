<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {
    $router->get('/', 'HomeController@index')->name('home');
    /*用户管理*/
    $router->resource('/account', 'Account\\AccountController');
    $router->resource('/locklistdata', 'Account\\AccountLockController');
    $router->get('/unlock/{id}', 'Account\\AccountLockController@unlock');

    /*举报管理*/
    $router->resource('/report', 'ReportController');

    /*公告管理*/
    $router->resource('/announce', 'AnnounceController');

    /*广告管理*/
    $router->resource('/ads_list', 'Ads\\AdsListController');
    $router->resource('/ads_pos', 'Ads\\AdsPosController');

    /*内容管理*/
        /*影片管理*/
    $router->resource('/manage_movie', 'Manage\\ManageMovieController');
        $router->any('/manage_movie/octopus/{id}', 'Api\\OctopusController@index');
        /*影片管理-图像资源接口类*/
        $router->post('/manage_movie/fileInput', 'Api\\ManageMovieSyncFileController@fileInput');
        $router->post('/manage_movie/map', 'Api\\ManageMovieSyncFileController@map');
        $router->post('/manage_movie/fileRemove', 'Api\\ManageMovieSyncFileController@fileRemove');

        $router->any('/getDirectors','Api\\DirectorController@get');

    /*评论*/
    $router->resource('/comment', 'Comment\\MovieCommentController');
    $router->resource('/filter', 'Comment\\FilterController');
    $router->resource('/recomment', 'Comment\\MovieReCommentController');

    /*网站管理*/
        /*热搜词*/
    $router->resource('/hotwords', 'Web\\HotWordsController');
        /*短评须知*/
    $router->any('/notes', 'Web\\CommentNotesController@index');
    $router->any('/notes/store', 'Web\\CommentNotesController@store');
        /*分享说明*/
    $router->any('/share', 'Web\\CommentShareController@index');
    $router->any('/share/store', 'Web\\CommentShareController@store');

    /*首页*/
        /*评论检测开关*/
    $router->any('/home/switch', 'HomeController@switch');
        /*缓存开关*/
    $router->any('/cache/clearcache/{id}', 'HomeController@clearCache' );

    /*意见反馈*/
    $router->resource('/complaint', 'ComplaintController');

    /*首页推荐 轮播*/
    $router->resource('/carousel', 'CarouselController');
    $router->post('/carousel/searchNumber', "CarouselController@getMovieStatistics");
});
