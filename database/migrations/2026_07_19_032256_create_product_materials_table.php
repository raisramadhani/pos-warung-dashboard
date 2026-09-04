<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resep produk: item bahan baku dan jumlah per porsi.
     * Saat produk terjual, stok bahan baku dikurangi sesuai resep ini via TransactionItemObserver.
     */
    public function up(): void
    {
        Schema::create('product_materials', function (Blueprint $table): void {
            $table->comment('Resep produk: item bahan baku dan jumlah per porsi. Saat produk terjual, stok bahan baku dikurangi sesuai resep ini');
            $table->id();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();
            $table->decimal('quantity_required', 10, 4)->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};
