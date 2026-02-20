<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'auth_token',
        'token_expires_at',
        'password_reset_otp',
        'otp_expires_at',
        'otp_attempts',
        'device_type',
        'device_token',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'auth_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_expires_at' => 'datetime',
        ];
    }
    public function role()
    {
        return $this->hasOne(Userroles::class, 'user_id', 'id')->with(['roles']);
    }
    
    public function userTokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    public function activeTokens(): HasMany
    {
        return $this->userTokens()->where('is_active', true)->where('expires_at', '>', now());
    }

    /**
     * Revoke all tokens for this user
     */
    public function revokeAllTokens(): void
    {
        UserToken::where('user_id', $this->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now()
            ]);
    }

    /**
     * Create a new token for the user
     */
    public function createToken(string $name, array $abilities = ['*'], $expiresAt = null)
    {
        $tokenString = \Illuminate\Support\Str::random(80);
        $tokenHash = hash('sha256', $tokenString);

        // Create token in format: user_id|token_string
        $plainTextToken = $this->id . '|' . $tokenString;

        return (object) [
            'plainTextToken' => $plainTextToken,
            'tokenHash' => $tokenHash,
            'accessToken' => (object) ['token' => $tokenHash]
        ];
    }

    /**
     * Get the roles associated with the user
     */
    public function userRoles()
    {
        return $this->hasMany(Userroles::class, 'user_id');
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roleIds): bool
    {
        return $this->userRoles()->whereIn('role_id', $roleIds)->exists();
    }
    
}
