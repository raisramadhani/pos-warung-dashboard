<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail item dalam distribusi.
     * quantity_sent = jumlah dikirim, quantity_received = jumlah diterima (diupdate saat merchant verifikasi).
     * StockMovementService mencatat mutasi stok saat item dibuat (source) dan saat distribusi selesai (destination).
     */
    public function up(): void
    {
        Schema::create('distribution_items', function (Blueprint $table) {
            $table->comment('Detail item dalam distribusi. quantity_sent = jumlah dikirim, quantity_received = jumlah diterima (diupdate saat merchant verifikasi)');
            $table->id();
            $table->foreignId('distribution_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->decimal('quantity_sent', 10, 4)->default(0);
            $table->decimal('quantity_received', 10, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_items');
    }
};
