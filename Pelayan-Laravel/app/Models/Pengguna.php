<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
abstract class Pengguna extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Membatasi akses tiap panel Filament berdasarkan peran (KNF-03).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isPelayan(),
            'pemilik' => $this->isPemilik(),
            default => false,
        };
    }

    public function isPelayan(): bool
    {
        return $this->role === 'pelayan';
    }

    public function isPemilik(): bool
    {
        return $this->role === 'pemilik';
    }

    public static function login(string $credential, string $password): bool
    {
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return Auth::attempt([
            $field => $credential,
            'password' => $password,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
