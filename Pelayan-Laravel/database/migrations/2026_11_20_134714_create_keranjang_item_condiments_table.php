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
        Schema::create('keranjang_item_condiment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keranjang_item_id')->constrained('keranjang_items')->cascadeOnDelete();
            $table->foreignId('condiment_id')->constrained('condiments')->cascadeOnDelete();
            $table->decimal('harga_condiment', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjang_item_condiment');
    }
};
