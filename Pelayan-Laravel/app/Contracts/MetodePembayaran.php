<?php

namespace App\Contracts;

interface MetodePembayaran
{
    public function prosesPembayaran(float $totalBayar): bool;

    public function getNamaMetode(): string;
}
