<?php

use App\Http\Controllers\Api\SwaggerController;
use Illuminate\Support\Facades\Route;

/**
 * Swagger API Documentation Routes
 * 
 * These routes handle serving the OpenAPI/Swagger documentation
 * in various formats and endpoints for compatibility with different
 * Swagger UI implementations and L5-Swagger package requirements.
 */

// Remove auto-generation middleware from here since it's now in L5-Swagger config
Route::middleware(['throttle:60,1'])->group(function () {
    // Main API documentation redirect to Swagger UI
    Route::get('/api-docs', function () {
        return redirect('/api/documentation');
    })->name('swagger.redirect');

    // L5 Swagger package routes group
    Route::prefix('v1/api')->group(function () {
        // Required by L5-Swagger package
        Route::get('/docs/{jsonFile?}', [SwaggerController::class, 'serveDocumentation'])
            ->name('l5-swagger.default.docs')
            ->where('jsonFile', '[a-zA-Z0-9\-_.]+\.json');
    });
    // Legacy documentation routes for backward compatibility
    Route::prefix('docs')->group(function () {
        // Handle query parameter format (?api-docs.json)
        Route::get('/', function () {
            return request()->has('api-docs.json') 
                ? app(SwaggerController::class)->serveDocumentation('api-docs.json')
                : redirect('/api/documentation');
        })->name('swagger.docs.query');
        
        // Handle path parameter format (/docs/api-docs.json)
        Route::get('/{jsonFile?}', [SwaggerController::class, 'serveDocumentation'])
            ->name('swagger.docs.path')
            ->where('jsonFile', '[a-zA-Z0-9\-_.]+\.json');
    });
});