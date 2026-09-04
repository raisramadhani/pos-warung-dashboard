<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hadiah "dapat" sebuah promo ketika syarat terpenuhi.
     * reward_type menentukan bagaimana hadiah diterapkan:
     * - free_item: produk gratis (product_id NULL berarti produk yang sama dengan kondisi)
     * - fixed_price: harga produk di-override menjadi nilai value
     * - percent_discount: potongan persen dari harga (value = persen)
     * - fixed_discount: potongan nominal rupiah (value = nominal)
     *
     * Guard hasTable(): di lingkungan lama tabel sudah ada (migrasi awal dihapus),
     * migrasi ini dilewati agar tidak konflik.
     */
    public function up(): void
    {
        if (Schema::hasTable('promotion_rewards')) {
            return;
        }

        Schema::create('promotion_rewards', function (Blueprint $table): void {
            $table->comment('Hadiah sebuah promo ketika syarat terpenuhi. reward_type menentukan cara hadiah diterapkan (free_item, fixed_price, percent_discount, fixed_discount)');
            $table->id();
            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete()
                ->comment('Promo pemilik hadiah');
            $table->string('reward_type')
                ->comment('Jenis hadiah: free_item, fixed_price, percent_discount, fixed_discount');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->comment('Untuk free_item: produk yang diberikan gratis. NULL = produk yang sama dengan kondisi');
            $table->unsignedInteger('quantity')
                ->nullable()
                ->comment('Untuk free_item: jumlah item gratis. Untuk fixed_price: jumlah item yang harganya di-override');
            $table->unsignedBigInteger('value')
                ->nullable()
                ->comment('fixed_price → harga baru; percent_discount → persen diskon; fixed_discount → nominal potongan; free_item → NULL');
            $table->timestamps();

            $table->index(['promotion_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_rewards');
    }
};
