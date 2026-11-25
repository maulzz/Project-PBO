<?php

namespace App\Contracts;

interface LaporanExporter
{
    /**
     * Mengekspor data tabel menjadi file laporan dan mengembalikan path file sementara.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function export(array $headers, array $rows, string $sheetName = 'Laporan'): string;
}
