<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pesanan')->unique();
            $table->foreignId('meja_id')->constrained('mejas')->restrictOnDelete();
            $table->string('nama_pengunjung');
            $table->timestamp('tanggal_pesanan')->useCurrent();
            $table->string('status_pesanan')->default('Pesanan Masuk'); // 'Pesanan Masuk', 'Diterima', 'Diproses', 'Selesai', 'Dibatalkan'
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->foreignId('pelayan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};
