<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
class LoginController extends BaseApiController
{
    /**
     * Allowed user roles for login
     */
    private const ALLOWED_ROLES = [9];

    #[OA\Post(
        path: "/auth/login",
        tags: ["Authentication V1"],
        summary: "User Login",
        description: "Authenticate user and return access token",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "email",
                        type: "string",
                        description: "User's email or phone number"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        description: "User's password"
                    ),
                    new OA\Property(
                        property: "device_type",
                        type: "string",
                        enum: ["ios", "android"],
                        description: "Type of device used for login"
                    ),
                    new OA\Property(
                        property: "device_token",
                        type: "string",
                        description: "Device token for push notifications"
                    )
                ],
                example: [
                    'email' => 'user@example.com',
                    'password' => 'password123',
                    'device_type' => 'ios',
                    'device_token' => 'device_token_example'
                ]
            )
        ),  
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent( 
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "userinfo",
                            type: "object",
                            description: "Authenticated user details"
                        ),
                        new OA\Property(
                            property: "token",
                            type: "string",
                            description: "Access token for authenticated user"
                        ),
                        new OA\Property(
                            property: "token_type",
                            type: "string",
                            description: "Type of the token (e.g., Bearer)"
                        ),
                        new OA\Property(
                            property: "expires_at",
                            type: "string",
                            format: "date-time",
                            description: "Token expiration time in ISO 8601 format"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent( 
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            description: "Error message describing the validation issue"
                        )
                    ]
                )
            )
        ]
    )]
    public function __invoke(Request $request)
    {
        
        $request->validate([
            'email' => 'required',
            'password' => 'required|string|min:12',
            'device_type'=>'required|string|in:ios,android',
            'device_token'=>'required|string'
        ]);

        $user = User::where(['email' => $request->email, 'status' => '1'])->with('role')->first();

        if(!$user){
            // Try finding user by phone if not found by email
            $user = User::where(['phone' => $request->email, 'status' => '1'])->with('role')->first();
        }
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        if (is_null($user->email_verified_at)) {
            throw ValidationException::withMessages([
                'email' => ['Your email address is not verified. Please verify your email to proceed.'],
            ]);
        }

        // Revoke all existing tokens (logout from all devices)
        $user->revokeAllTokens();

        // Create new token
        $expiresAt = now()->addDays(30);
        $tokenResult = $user->createToken('auth-token', ['*'], $expiresAt);
        $token = $tokenResult->plainTextToken;

        // Update user's current token info
        $user->update([
            'auth_token' => $tokenResult->tokenHash,
            'token_expires_at' => $expiresAt,
            'device_type'=>$request->device_type,
            'device_token'=>$request->device_token
        ]);

        // Store token information in user_tokens table
        UserToken::create([
            'user_id' => $user->id,
            'token_name' => 'auth-token',
            'token' => $tokenResult->tokenHash,
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
            'is_active' => true,
        ]);
        $userInfo =  $user->makeHidden(['email_verified_at', 'updated_at', 'auth_token', 'token_expires_at','password_reset_otp','otp_expires_at','otp_attempts','status','created_at','device_type','device_token','role']);
        $userInfo->role_id = $user->role->id;
        $userInfo->role_name = $user->role->roles->rolename ?? null;
        $userInfo->department = $user->role->roles->departments->title ?? null;
        return $this->success([
            'userinfo' => $userInfo,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString()
        ], 'Login successful');
    }
}

