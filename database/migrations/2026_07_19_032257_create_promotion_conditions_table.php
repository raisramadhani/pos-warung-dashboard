<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Syarat "beli" sebuah promo: produk atau kategori mana yang harus dibeli
     * dengan jumlah minimum tertentu agar promo berlaku.
     * product_id dan category_id bersifat eksklusif — hanya salah satu yang boleh diisi.
     *
     * Guard hasTable(): di lingkungan lama tabel sudah ada (migrasi awal dihapus),
     * migrasi ini dilewati agar tidak konflik.
     */
    public function up(): void
    {
        if (Schema::hasTable('promotion_conditions')) {
            return;
        }

        Schema::create('promotion_conditions', function (Blueprint $table): void {
            $table->comment('Syarat beli sebuah promo: produk/kategori yang harus dibeli dengan jumlah minimum. product_id dan category_id eksklusif (hanya salah satu)');
            $table->id();
            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete()
                ->comment('Promo pemilik syarat');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->comment('Produk yang menjadi syarat pembelian. NULL jika syarat berdasarkan kategori');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('Kategori yang menjadi syarat pembelian. NULL jika syarat berdasarkan produk. Hanya salah satu dari product_id/category_id yang diisi');
            $table->unsignedInteger('min_quantity')
                ->default(1)
                ->comment('Jumlah minimum produk yang harus dibeli agar promo berlaku');
            $table->timestamps();

            $table->index(['promotion_id', 'product_id']);
            $table->index(['promotion_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_conditions');
    }
};
