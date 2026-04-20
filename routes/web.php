<?php

use Contensio\FakePosts\Http\Controllers\Admin\FakePostsController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('contensio.route_prefix', 'account'))
    ->middleware(['web', 'contensio.auth', 'contensio.admin'])
    ->group(function () {
        Route::get('/fake-posts',            [FakePostsController::class, 'index'])     ->name('contensio-fake-posts.index');
        Route::post('/fake-posts/generate',  [FakePostsController::class, 'generate'])  ->name('contensio-fake-posts.generate');
        Route::delete('/fake-posts/all',     [FakePostsController::class, 'deleteAll']) ->name('contensio-fake-posts.delete-all');
    });
