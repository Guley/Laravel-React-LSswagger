<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\ResetPasswordWithOtpEmail;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 3;

    /**
     * Generate and send OTP for password reset
     */
    public function sendPasswordResetOtp(object $user, bool $isNotified = true): array
    {
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // $otp = $this->generateOtp();

        // $user->update([
        //     'password_reset_otp' => Hash::make($otp),
        // ]);

        $otp = $this->generateOtp();
        
        $user->update([
            'password_reset_otp' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'otp_attempts' => 0
        ]);

        // Send OTP via email
        if($isNotified){
            $this->sendOtpEmail($user, $otp);
            // Optionally, send OTP via SMS
            // $this->sendOtpPhone($user, $otp);
        }

        return ['otp' => $otp];
    }

    /**
     * Verify OTP for password reset
     */
    public function verifyPasswordResetOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$user->password_reset_otp) {
            return ['valid' => false, 'message' => 'No OTP found for this email'];
        }

        if (now()->isAfter($user->otp_expires_at)) {
            $this->clearOtp($user);
            return ['valid' => false, 'message' => 'OTP has expired'];
        }

        if ($user->otp_attempts >= self::MAX_ATTEMPTS) {
            $this->clearOtp($user);
            return ['valid' => false, 'message' => 'Maximum OTP attempts exceeded'];
        }

        if (!Hash::check($otp, $user->password_reset_otp)) {
            $user->increment('otp_attempts');
            return ['valid' => false, 'message' => 'Invalid OTP'];
        }
        $this->clearOtp($user);
        return ['valid' => true, 'user' => $user];
    }

    /**
     * Reset password using verified OTP
     */
    public function resetPasswordWithOtp(string $email, string $otp, string $newPassword): bool
    {
        $verification = $this->verifyPasswordResetOtp($email, $otp);

        if (!$verification['valid']) {
            return false;
        }

        $user = $verification['user'];
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        $this->clearOtp($user);
        return true;
    }

    /**
     * Generate random OTP
     */
    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Clear OTP data from user
     */
    private function clearOtp(User $user): void
    {
        $user->update([
            'password_reset_otp' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0
        ]);
    }

    /**
     * Send OTP via email
     */
    private function sendOtpEmail(User $user, string $otp): void
    {
        Mail::to($user->email)->send(new ResetPasswordWithOtpEmail($user, $otp));
    }
    private function sendOtpPhone(User $user, string $otp): void
    {
        // Logic to send OTP via SMS
    }
}
