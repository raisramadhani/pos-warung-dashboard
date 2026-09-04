<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat depresiasi aset per periode (metode garis lurus).
     * Satu entri per aset per bulan untuk pencatatan penyusutan.
     */
    public function up(): void
    {
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->comment('Riwayat depresiasi aset per periode (metode garis lurus). Satu entri per aset per bulan');
            $table->id();
            $table->foreignId('asset_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('period_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('book_value_before', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->timestamps();
            $table->unique(['asset_id', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
