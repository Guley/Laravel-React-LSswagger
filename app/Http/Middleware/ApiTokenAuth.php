<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    /**
     * Handle API token authentication
     * 
     * This middleware:
     * 1. Extracts bearer token from Authorization header
     * 2. Validates token format (user_id|token_string)
     * 3. Checks token against UserToken model
     * 4. Verifies token is active and not expired
     * 5. Sets authenticated user for the request
     * 6. Updates token last_used_at timestamp
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'The provided API token is missing'], 401);
        }

        // Parse token (format: user_id|token_string)
        $tokenParts = explode('|', $token, 2);
        if (count($tokenParts) !== 2) {
            return response()->json(['message' => 'The provided API token is invalid or expired'], 401);
        }
        

        [$userId, $tokenString] = $tokenParts;


        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'The provided API token is invalid or expired'], 401);
        }
        if(!in_array($user->status, [1])) {
            return response()->json(['message' => 'The user associated with the API token is not active'], 401);
        }


        $tokenHash = hash('sha256', $tokenString);

        // Find active token
        $userToken = UserToken::where('user_id', $userId)
            ->where('token', $tokenHash)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$userToken) {
            return response()->json(['message' => 'The provided API token is invalid or expired'], 401);
        }

        // Update last used timestamp
        $userToken->update(['last_used_at' => now()]);

        // Set authenticated user
        $user = User::find($userId);
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
