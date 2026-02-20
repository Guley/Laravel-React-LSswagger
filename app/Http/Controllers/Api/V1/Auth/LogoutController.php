<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\UserToken;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
class LogoutController extends BaseApiController
{
    #[OA\Post(
        path: "/auth/logout",
        tags: ["Authentication V1"],
        summary: "User Logout",
        description: "Logout user and revoke access token",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            description: "Logout success message"
                        )
                    ]
                )
            )
        ]
    )]
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->bearerToken();

        if ($currentToken) {
            // Parse token to get the actual token string
            $tokenParts = explode('|', $currentToken, 2);
            if (count($tokenParts) === 2) {
                $hashedToken = hash('sha256', $tokenParts[1]);
                
                // Deactivate token in user_tokens table
                UserToken::where('user_id', $user->id)
                    ->where('token', $hashedToken)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                
                // Clear user's current token info if this was the active token
                if ($user->auth_token === $hashedToken) {
                    $user->update([
                        'auth_token' => null,
                        'token_expires_at' => null,
                        'device_type'=>null,
                        'device_token'=>null
                    ]);
                }
            }
        }

        return $this->success(null, 'Logged out successfully');
    }
}
