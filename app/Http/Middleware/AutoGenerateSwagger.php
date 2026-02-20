<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AutoGenerateSwagger
{
    public function handle(Request $request, Closure $next)
    {
        // Only generate if L5_SWAGGER_GENERATE_ALWAYS is true
        if (config('l5-swagger.defaults.generate_always', false)) {
            try {
                // Clear the cache and regenerate
                Artisan::call('l5-swagger:generate', ['--all' => true]);
                Log::info('Swagger documentation regenerated');
            } catch (\Exception $e) {
                Log::error('Failed to generate Swagger docs: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
