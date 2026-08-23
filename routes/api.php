<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ScholarshipController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AdmobSettingController;
use App\Http\Controllers\Api\AdEventController;
use App\Http\Controllers\Api\AppVersionController;
use Illuminate\Support\Facades\Route;

Route::get('/settings/admob', [AdmobSettingController::class, 'index']);
Route::get('/app-version', [AppVersionController::class, 'index']);

// Ad impression/click logging — public (guests see ads too), throttled
// per-device separately since it's a public write endpoint.
Route::post('/ads/events', [AdEventController::class, 'store'])->middleware('throttle:ad-events');

/*
|--------------------------------------------------------------------------
| API Routes — Scholarship App
|--------------------------------------------------------------------------
|
| Rate limiting: 'api' throttle is 60/min by default.
| Auth routes use a stricter 20/min limit.
|
*/

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::prefix('auth')->middleware('throttle:20,1')->group(function () {
    // OTP
    Route::post('send-register-otp', [AuthController::class, 'sendRegisterOtp']);
    Route::post('send-forgot-otp',   [AuthController::class, 'sendForgotOtp']);
    Route::post('reset-password',    [AuthController::class, 'resetPassword']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::post('fcm-token',       [AuthController::class, 'updateFcmToken']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::post('profile',         [AuthController::class, 'updateProfile']);
    });
});

// ─── Public: Scholarships ─────────────────────────────────────────────────────
Route::prefix('scholarships')->group(function () {
    Route::get('/',                [ScholarshipController::class, 'index']);
    Route::get('featured',         [ScholarshipController::class, 'featured']);
    Route::get('latest',           [ScholarshipController::class, 'latest']);
    Route::get('{slug}',           [ScholarshipController::class, 'show']);
    Route::get('{slug}/related',   [ScholarshipController::class, 'related']);
});

// ─── Public: Categories ───────────────────────────────────────────────────────
Route::prefix('categories')->group(function () {
    Route::get('/',                        [CategoryController::class, 'index']);
    Route::get('{slug}/scholarships',      [CategoryController::class, 'scholarships']);
});

// ─── Public: Blog Posts ───────────────────────────────────────────────────────
Route::prefix('posts')->group(function () {
    Route::get('/',          [BlogPostController::class, 'index']);
    Route::get('featured',   [BlogPostController::class, 'featured']);
    Route::get('{slug}',     [BlogPostController::class, 'show']);
});

// ─── Authenticated: Applications ─────────────────────────────────────────────
Route::prefix('applications')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/',      [ApplicationController::class, 'index']);
        Route::post('/',     [ApplicationController::class, 'store']);
        Route::get('{id}',   [ApplicationController::class, 'show']);
    });
