<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    public function handle(Request $request, Closure $next, string $version = null): Response
    {
        if ($version) {
            $request->attributes->set('api_version', $version);
        }

        // Add version to response headers
        $response = $next($request);
        
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('API-Version', $version ?? 'v1');
        }

        return $response;
    }
}
