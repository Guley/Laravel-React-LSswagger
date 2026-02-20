<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Default API info route
Route::get('/', function () {
    return response()->json([
        'message' => 'Mobile API',
        'version' => '1.0.0',
        'documentation' => url('/api/documentation'),
        'available_versions' => ['v1']
    ]);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'service' => 'Mobile API'
    ]);
});