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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->unique()->constrained('pesanans')->cascadeOnDelete();
            $table->string('metode_pembayaran'); // 'online', 'kasir'
            $table->decimal('total_bayar', 12, 2);
            $table->string('status_pembayaran')->default('menunggu'); // 'menunggu', 'lunas', 'gagal', 'dibatalkan'
            $table->string('transaksi_ref')->nullable(); // snap token atau no ref kasir
            $table->timestamp('waktu_bayar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
