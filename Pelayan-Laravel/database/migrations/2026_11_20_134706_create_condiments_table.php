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
        Schema::create('condiments', function (Blueprint $table) {
            $table->id();
            $table->string('nama_condiment');
            $table->decimal('harga_condiment', 12, 2)->default(0);
            $table->boolean('status_ketersediaan')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condiments');
    }
};
