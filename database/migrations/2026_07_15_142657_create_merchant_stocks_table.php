<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saldo stok saat ini per merchant per item (running balance).
     * Kolom quantity diupdate oleh StockMovementService setiap ada mutasi.
     * unique(['merchant_id', 'item_id']) memastikan satu saldo per kombinasi.
     */
    public function up(): void
    {
        Schema::create('merchant_stocks', function (Blueprint $table) {
            $table->comment('Saldo stok saat ini per merchant per item (running balance). Diupdate oleh StockMovementService setiap ada mutasi');
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 4)->default(0);
            $table->timestamps();
            $table->unique(['merchant_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_stocks');
    }
};
