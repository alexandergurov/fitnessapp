<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\CharityController;
use App\Http\Controllers\FaqTopicController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\PostCategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutCategoryController;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AppleLoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => ['auth:api']], function() {
    //App user related
    Route::post('/appuser/update/{id}', [UserController::class,'update']);
    Route::post('/appuser/add-device/{id}', [UserController::class, 'addDevice']);
    Route::get('/currentuser', [UserController::class, 'getCurrent']);
    Route::post('/payment/save', [PaymentController::class, 'saveReceipt']);
    Route::post('/password-change', [UserController::class, 'passwordChange']);
    Route::post('/logout', [PassportAuthController::class, 'logout']);
});
Route::group(['middleware' => ['auth:api','paywall:api']], function() {
    //App user related
    Route::get('/appusers', [UserController::class, 'getList']);
    Route::get('/appuser/{id}', [UserController::class, 'getOne']);
    Route::get('/welcome-user', [UserController::class, 'welcome']);

    // Blog posts related
    Route::get('/posts', [PostController::class, 'getList']);
    Route::get('/post/{id}', [PostController::class, 'getOne']);
    Route::get('/posts/category/{id}', [
        PostController::class,
        'getListByCategory'
    ]);
    Route::get('/posts/instructor/{id}', [
        PostController::class,
        'getListByInstructor'
    ]);
    Route::get('/categories', [PostCategoryController::class, 'getList']);

    // Instructors related
    Route::get('/instructors', [InstructorController::class, 'getList']);
    Route::get('/instructor/{id}', [InstructorController::class, 'getOne']);

    // Workouts related
    Route::get('/workouts', [WorkoutController::class, 'getList']);
    Route::get('/workouts/leaderboard', [WorkoutController::class, 'getLeaderboard']);
    Route::get('/workouts/favorite', [WorkoutController::class, 'favoriteList']);
    Route::get('/workout/{id}', [WorkoutController::class, 'getOne']);
    Route::get('/workout-categories', [WorkoutCategoryController::class, 'getList']);
    Route::get('/workout-category/{id}', [WorkoutCategoryController::class, 'getOne']);
    Route::post('/workout/{id}/set-status', [WorkoutController::class, 'setUsersStatus']);
    Route::post('/workout/{id}/toggle-favorite', [WorkoutController::class, 'toggleFavorite']);
    Route::post('/workout/{id}/post-feedback', [WorkoutController::class, 'postFeedback']);

    // Challenges related
    Route::get('/challenges', [ChallengeController::class, 'getList']);
    Route::get('/challenge/{id}', [ChallengeController::class, 'getOne']);
    Route::post('/challenge/{id}/complete', [ChallengeController::class, 'complete']);
    Route::get('/challenge/all-results/{id}', [ChallengeController::class, 'allResults']);
    Route::get('/challenges/user-results', [ChallengeController::class, 'usersResults']);

    // Badges related
    Route::get('/badge/{id}', [BadgeController::class, 'getOne']);
    Route::get('/badges/status', [BadgeController::class, 'usersBadges']);
    Route::post('/badges/assign', [BadgeController::class, 'triggerTriggers']);

    // Charities related
    Route::get('/charities', [CharityController::class, 'getList']);
    Route::get('/charities/country/{country}', [
        CharityController::class,
        'getListForCountry'
    ]);
    Route::get('/charity/{id}', [CharityController::class, 'getOne']);

    // FAQ related
    Route::get('/faq-topics', [FaqTopicController::class, 'getList']);
    Route::get('/faq-items', [FaqTopicController::class, 'getItems']);
    Route::get('/faq-topic/{id}', [FaqTopicController::class, 'getOne']);
});

Route::post('/validate-token', [UserController::class, 'validateToken']);
Route::post('/password-reset', [UserController::class, 'passwordReset']);
Route::post('/password-save', [UserController::class, 'setPassword']);
Route::post('/login', [PassportAuthController::class, 'login']);
Route::post('/register', [PassportAuthController::class, 'register']);
Route::post('/refresh', [PassportAuthController::class, 'refresh']);
Route::post('/apple/login',[AppleLoginController::class, 'appleLogin']);
