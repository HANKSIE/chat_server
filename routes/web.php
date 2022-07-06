<?php

use App\Http\Controllers\Socialite\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

require __DIR__ . '/auth.php';
Route::middleware('auth:sanctum')->group(function () {
    Route::resource('groups', GroupController::class)->except(['create', 'edit']);
    Route::get('/user', function (Request $request) {
        return response()->json(['user' => $request->user()]);
    });
});
