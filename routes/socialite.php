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
            Route::delete('revoke', [FriendController::class, 'revokeRequest'])->name('friend.request.revoke');
        });
        Route::prefix('requests')->group(function () {
            Route::get('receive/{perPage}', [FriendController::class, 'requestsToMe'])->name('friend.request.to');
            Route::get('sent/{perPage}', [FriendController::class, 'requestsFromMe'])->name('friend.request.from');
        });
    });

    Route::prefix('group')->group(function () {
        Route::prefix('{groupID}')->group(function () {
            Route::prefix('messages')->group(function () {
                Route::get('search/{perPage?}/{keyword?}', [MessageController::class, 'simplePaginate'])
                    ->name('message.simple-paginate');
                Route::get('paginate/{perPage?}', [MessageController::class, 'cursorPaginate'])
                    ->name('message.cursor-paginate');
            });
            Route::prefix('message')->group(function () {
                Route::put('mark-as-read', [MessageController::class, 'markAsRead'])
                    ->name('message.mark-as-read');
            });
            Route::resource('messages', MessageController::class)->only('store');
            Route::get('message-reads', [GroupController::class, 'messageReads']);
        });
    });
    Route::prefix('groups')->group(function () {
        Route::get('recent-contact/{isOneToOne}/{perPage?}', [GroupController::class, 'recentContact'])
            ->name('group.recent-contact');

    });
    Route::resource('groups', GroupController::class)->only(['index']);
    Route::get('users/search/{perPage}/{keyword?}', [FriendController::class, 'findNewFriendSimplePaginate']);
});
