<?php

use App\Http\Controllers\Socialite\FriendController;
use App\Http\Controllers\Socialite\GroupController;
use App\Http\Controllers\Socialite\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('friends')->group(function () {
        Route::get('search/{perPage}/{keyword?}', [FriendController::class, 'simplePaginate'])
            ->name('friend.simple-paginate');
        Route::delete('', [FriendController::class, 'unfriend'])->name('unfriend');
        Route::prefix('request')->group(function () {
            Route::post('send', [FriendController::class, 'sendRequest'])->name('friend.request.send');
            Route::post('accept', [FriendController::class, 'acceptRequest'])->name('friend.request.accept');
            Route::post('deny', [FriendController::class, 'denyRequest'])->name('friend.request.deny');
        });
        Route::prefix('requests')->group(function () {
            Route::get('receive/{perPage}', [FriendController::class, 'requestsToMe'])->name('friend.request.to');
            Route::get('sent/{perPage}', [FriendController::class, 'requestsFromMe'])->name('friend.request.from');
        });
    });
    Route::prefix('messages')->group(function () {
        Route::get('search/{groupID}/{perPage?}/{keyword?}', [MessageController::class, 'simplePaginate'])
            ->name('message.simple-paginate');
    });
    Route::resource('messages', MessageController::class)->only('store');
    Route::prefix('groups')->group(function () {
        Route::get('recent-contact/{isOneToOne}/{perPage?}', [GroupController::class, 'recentContact'])
            ->name('groups.recent-contact');
    });
    Route::resource('groups', GroupController::class)->only(['index']);
    Route::get('users/search/{perPage}/{keyword?}', [FriendController::class, 'findNewFriendSimplePaginate']);
});
