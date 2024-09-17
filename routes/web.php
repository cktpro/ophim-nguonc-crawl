<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'Ophim\Crawler\OphimCrawler\Controllers',
], function () {
    Route::get('/plugin/ophim-crawler', 'CrawlController@showCrawlPage');
    Route::get('/plugin/nguonc-crawler', 'CrawlController@showCrawlPageNguonc');
    Route::get('/plugin/ophim-crawler/options', 'CrawlerSettingController@editOptions');
    Route::put('/plugin/ophim-crawler/options', 'CrawlerSettingController@updateOptions');
    Route::get('/plugin/ophim-crawler/fetch', 'CrawlController@fetch');
    Route::get('/plugin/ophim-crawler/fetch_nguonc', 'CrawlController@fetch_nguonc');
    Route::post('/plugin/ophim-crawler/crawl', 'CrawlController@crawl');
    Route::post('/plugin/ophim-crawler/crawl_nguonc', 'CrawlController@crawl_nguonc');
    Route::post('/plugin/ophim-crawler/get-movies', 'CrawlController@getMoviesFromParams');
});
