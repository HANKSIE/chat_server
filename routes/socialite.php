<?php

use App\Http\Controllers\Socialite\FriendController;
use App\Http\Controllers\Socialite\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('friends')->group(function () {
        Route::get('search/{perPage}/{keyword?}', [FriendController::class, 'simplePaginate'])
            ->name('friend.simple-paginate');
    });
    Route::prefix('messages')->group(function () {
        Route::get('search/{groupID}/{perPage?}/{keyword?}', [MessageController::class, 'simplePaginate'])
            ->name('message.simple-paginate');
    });
    Route::resource('messages', MessageController::class)->only('store');
});
