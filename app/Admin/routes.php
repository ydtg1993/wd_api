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
    $router->resource('/report', 'ReportController');
    $router->resource('/announce', 'AnnounceController');
    /*广告管理*/
    $router->resource('/ads_list', 'AdsListController');
    $router->resource('/ads_pos', 'AdsPosController');
    /*内容管理*/
    $router->resource('/manage_movie', 'ManageMovieController');
});
