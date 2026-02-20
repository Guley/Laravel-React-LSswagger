<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Userroles extends Model
{
    /** @use HasFactory<\Database\Factories\RolesFactory> */
    use HasFactory;
    protected $table = 'user_roles';
     protected $fillable = [
        'role_id',
        'user_id'
    ];
    public function roles()
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
