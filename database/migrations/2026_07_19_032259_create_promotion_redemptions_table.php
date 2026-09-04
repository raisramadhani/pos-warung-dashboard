<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log pemakaian promo pada transaksi.
     * Setiap kali promo berhasil diterapkan saat checkout, satu baris dicatat di sini
     * untuk laporan "promo paling sering dipakai" dan audit trail.
     *
     * Guard hasTable(): di lingkungan lama tabel sudah ada (migrasi awal dihapus),
     * migrasi ini dilewati agar tidak konflik.
     */
    public function up(): void
    {
        if (Schema::hasTable('promotion_redemptions')) {
            return;
        }

        Schema::create('promotion_redemptions', function (Blueprint $table): void {
            $table->comment('Log pemakaian promo pada transaksi. Dicatat setiap kali promo berhasil diterapkan saat checkout');
            $table->id();
            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete()
                ->comment('Promo yang dipakai');
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('Transaksi tempat promo dipakai');
            $table->foreignId('transaction_item_id')
                ->nullable()
                ->constrained('transaction_items')
                ->nullOnDelete()
                ->comment('Baris item tempat diskon berlaku. NULL jika diskon berlaku di tingkat keranjang');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->comment('Pelanggan yang menerima promo. NULL = pelanggan umum/tanpa identitas');
            $table->unsignedBigInteger('discount_amount')
                ->comment('Nilai diskon yang diterima pelanggan');
            $table->timestamp('created_at')
                ->useCurrent()
                ->comment('Waktu promo dipakai');

            $table->index(['promotion_id', 'transaction_id']);
            $table->index(['promotion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemptions');
    }
};
