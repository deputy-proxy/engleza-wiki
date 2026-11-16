<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'author_id');
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function savedResources()
    {
        return $this->hasMany(SavedResource::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
