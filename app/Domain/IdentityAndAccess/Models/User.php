<?php

namespace App\Domain\IdentityAndAccess\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\IdentityAndAccess\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUlids;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'annual_leave_allowance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isManager(): bool 
    {
        return in_array($this->role, [UserRole::Manager, UserRole::Admin]);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new(); 
    }
}
