<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiTokenAuth;
Route::prefix('v1')->middleware(['api.version:v1','throttle:api'])->name('api.v1.')->group(function () {
    // Public authentication routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', LoginController::class)->name('login');
        Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('forgot-password');
        Route::post('set-password', [ForgotPasswordController::class, 'resetPassword'])->name('set-password');
        Route::post('validate-otp', [ForgotPasswordController::class, 'validateOtp'])->name('validate-otp');
    });
    
    // Protected routes - using custom token authentication middleware
    Route::middleware(ApiTokenAuth::class)->group(function () {
        // Auth routes requiring authentication
        Route::post('auth/logout', LogoutController::class)->name('auth.logout');
        // Dashboard route
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });
});
