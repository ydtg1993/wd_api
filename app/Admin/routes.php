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
    $router->resource('/account', 'AccountController');
    $router->resource('/locklistdata', 'AccountLockController');
    $router->get('/unlock/{id}', 'AccountLockController@unlock');
    /*举报管理*/
    $router->resource('/report', 'ReportController');
    $router->resource('/announce', 'AnnounceController');
    /*广告管理*/
    $router->resource('/ads_list', 'AdsListController');
    $router->resource('/ads_pos', 'AdsPosController');

    /*内容管理*/
        /*影片管理*/
    $router->resource('/manage_movie', 'ManageMovieController');
    $router->post('/manage_movie/create', 'ManageMovieController@create');
    $router->any('/manage_movie/{id}/edit', 'ManageMovieController@edit');
        /*影片管理-图像资源接口类*/
        $router->post('/manage_movie/fileInput', 'Api\\ManageMovieSyncFileController@fileInput');
        $router->post('/manage_movie/map', 'Api\\ManageMovieSyncFileController@map');
        $router->post('/manage_movie/fileRemove', 'Api\\ManageMovieSyncFileController@fileRemove');
    /*评论*/
    $router->resource('/comment', 'MovieCommentController');
    $router->resource('/filter', 'FilterController');
    $router->resource('/recomment', 'MovieReCommentController');

    /*网站管理*/
        /*热搜词*/
    $router->any('/hotwords', 'HotWordsController@index');
    $router->any('/hotwords/store', 'HotWordsController@store');
        /*短评须知*/
    $router->any('/notes', 'CommentNotesController@index');
    $router->any('/notes/store', 'CommentNotesController@store');
        /*分享说明*/
    $router->any('/share', 'CommentShareController@index');
    $router->any('/share/store', 'CommentShareController@store');
        /*评论检测开关*/
    $router->any('/home/switch', 'HomeController@switch');
        /*缓存开关*/
    $router->post('/cache/clearcache/{id}', 'CacheManageController@clearCache' );
        /*意见反馈*/
    $router->resource('/complaint', 'ComplaintController');

    /*首页推荐 轮播*/
    $router->resource('/carousel', 'CarouselController');
    $router->post('/carousel/create', 'CarouselController@create');
    $router->any('/carousel/{id}/edit', 'CarouselController@edit');
    $router->post('/carousel/searchNumber', "CarouselController@getMovieStatistics");
});
