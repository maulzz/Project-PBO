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
        Schema::table('pesanans', function (Blueprint $table) {
            $table->foreignId('dibatalkan_oleh')->nullable()->after('pelayan_id')->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_dibatalkan')->nullable()->after('dibatalkan_oleh');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->foreignId('dikonfirmasi_oleh')->nullable()->after('waktu_bayar')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibatalkan_oleh');
            $table->dropColumn('waktu_dibatalkan');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dikonfirmasi_oleh');
        });
    }
};
