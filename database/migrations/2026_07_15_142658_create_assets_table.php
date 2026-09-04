<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aset/alat (item bertipe Tool) dengan pencatatan depresiasi bulanan.
     * Mencatat tanggal & harga akuisisi. Otomatis dibuat oleh GoodsReceiptObserver
     * saat verifikasi penerimaan barang, dan oleh DistributionObserver saat
     * distribusi alat ke merchant selesai (merchant_id diisi).
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->comment('Aset/alat (item bertipe Tool) dengan pencatatan depresiasi bulanan. Otomatis dibuat oleh GoodsReceiptObserver saat verifikasi dan DistributionObserver saat distribusi alat ke merchant');
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->integer('useful_life_months')->nullable();
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->string('depreciation_method')->default('non_depreciable');
            $table->string('status')->default('active');
            $table->date('last_depreciation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
