<?php

use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\WorkoutLeaderboardController;
use App\Http\Controllers\ChallengeLeaderboardController;
use App\Http\Controllers\ContentBaseController;
use App\Http\Controllers\VimeoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinessAppAuthController;
use App\Http\Controllers\WorkoutController;

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

Route::get('/', [AuthController::class, 'adminLoginRedirect']);
Route::get('/user/logout', [AuthController::class, 'userLogout']);

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::get('video-info', [VimeoController::class, 'getInfo']);
    Route::post('create-routines', [WorkoutController::class, 'massRoutinesCreation']);
    Route::post('login', [FinessAppAuthController::class, 'postLogin']);
});

Route::get('resizeImage', [ImageController::class, 'resizeImage']);

Route::post('resizeImagePost', [ImageController::class, 'resizeImagePost'])->name('resizeImagePost');

Route::get('admin/getcsv/{slug}', [ContentBaseController::class, 'exportCsv'])->name('getCsv');
Route::get('admin/export-csv/users-workouts', [WorkoutLeaderboardController::class, 'exportCsv'])->name('Wleaderboard.getCsv');
Route::get('admin/challenge-leaderboard', [ChallengeLeaderboardController::class, 'index'])->name('challenge.redirect');
Route::get('admin/challenge-leaderboard/{id}', [ChallengeLeaderboardController::class, 'index'])->name('challenge.leaderboard');
Route::get('admin/export-csv/challenge-leaderboard/{id}', [ChallengeLeaderboardController::class, 'exportCsv'])->name('Cleaderboard.getCsv');
