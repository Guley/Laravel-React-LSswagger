<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
class ForgotPasswordController extends BaseApiController
{
    private OtpService $otpService;
    private const ALLOWED_ROLES = [9];
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    #[OA\Post(
        path: "/auth/forgot-password",
        tags: ["Reset Password"],
        summary: "Send Password Reset Email",
        description: "Send password reset link to user's email",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "email",
                        type: "string",
                        description: "User's email or phone number"
                    )
                ],
                example: [
                    'email' => 'user@example.com'
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
                            property: "message",
                            type: "string",
                            description: "Success message"
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
    
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);
         $user = User::where(['email' => $request->email, 'status' => '1'])->with('role')->first();
        if(!$user){
            // Try finding user by phone if not found by email
            $user = User::where(['phone' => $request->email, 'status' => '1'])->with('role')->first();
        }
        
        if (!$user) {
            return $this->error('No active user found with the provided email or phone.', 422);
        }
        

        $sent = $this->otpService->sendPasswordResetOtp($user);
        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone
        ];

        if ($sent) {
            return $this->success(
                $userInfo,
                'Password reset OTP sent to your email address and phone number.'
            );
        }

        return $this->error('Unable to send OTP. Please try again.', 500);
    }

    #[OA\Post(
        path: "/auth/validate-otp",
        tags: ["Reset Password"],
        summary: "Validate OTP",
        description: "Validate the OTP sent to user's email",
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
                        property: "otp",
                        type: "string",
                        description: "OTP sent to user's email or phone"
                    )
                ],
                example: [
                    'email' => 'user@example.com',
                    'otp' => '123456'
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
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    description: "User ID"
                                ),
                                new OA\Property(
                                    property: "name",
                                    type: "string",
                                    description: "User name"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    description: "User email"
                                ),
                                new OA\Property(
                                    property: "phone",
                                    type: "string",
                                    description: "User phone number"
                                ),
                                new OA\Property(
                                    property: "otp",
                                    type: "string",
                                    description: "OTP for password reset"
                                )
                            ]
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
                            description: "Error message"
                        )
                    ]
                )
            )
        ]
    )]  
    public function validateOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email,status,1',
            'otp' => 'required|string|size:6',
        ]);

        $valid = $this->otpService->verifyPasswordResetOtp(
            $request->email,
            $request->otp
        );
        if ($valid && $valid['valid']) {
            $user = User::where(['email' => $request->email, 'status' => '1'])->with('role')->first();
            $sent = $this->otpService->sendPasswordResetOtp($user,false);
            $userInfo = [
                'id' => $valid['user']->id,
                'name' => $valid['user']->name,
                'email' => $valid['user']->email,
                'phone' => $valid['user']->phone,
                'otp' => $sent['otp']
            ];
            return $this->success($userInfo, 'OTP verified successfully. You can proceed to reset your password.');
        }

        return $this->error('Invalid or expired OTP. Please try again.', 422);
    }

    #[OA\Post(
        path: "/auth/set-password",
        tags: ["Reset Password"],
        summary: "Set user password after OTP validation",
        description: "Set user password after OTP validation",
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
                        property: "otp",
                        type: "string",
                        description: "OTP sent to user's email or phone"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        description: "New password for the user"
                    ),
                    new OA\Property(
                        property: "password_confirmation",
                        type: "string",
                        description: "Password confirmation"
                    )
                ],
                example: [
                    'email' => 'user@example.com',
                    'otp' => '123456',
                    'password' => 'NewPassword123',
                    'password_confirmation' => 'NewPassword123'
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
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    description: "User ID"
                                ),
                                new OA\Property(
                                    property: "name",
                                    type: "string",
                                    description: "User name"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    description: "User email"
                                ),
                                new OA\Property(
                                    property: "phone",
                                    type: "string",
                                    description: "User phone number"
                                )
                            ]
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
                            description: "Error message"
                        )
                    ]
                )
            )
        ]
    )]
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email,status,1',
            'otp' => 'required|string|size:6',
            'password' => [
                'required',
                'string',
                'confirmed',
                'different:old_password',
                'max:20',
                    'min:8', // Minimum length (optional)
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+$/'  // At least one uppercase letter and one number and one lowercase letter
            ],
            'password_confirmation' => 'required|string|min:8|same:password',
        ],[
                'password.different' => 'Password must be different from the old password.',
                'password.regex' => 'Password must contain at least one uppercase letter and one number and minimum one lowercase letter.',
                'password.max' => 'Password must not exceed 20 characters.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

        $reset = $this->otpService->resetPasswordWithOtp(
            $request->email,
            $request->otp,
            $request->password
        );

        $user = User::where('email', $request->email)->first();

        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone
        ];

        if ($reset) {
            return $this->success($userInfo, 'Password has been reset successfully.');
        }

        return $this->error('Failed to reset password. Invalid or expired OTP.', 422);
    }
}
