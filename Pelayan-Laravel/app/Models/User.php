<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Pengguna
{
    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'pelayan_id');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
