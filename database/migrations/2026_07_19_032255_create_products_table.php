<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produk jual per merchant.
     * Punya resep (product_materials) yang dikurangi stoknya saat transaksi.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->comment('Produk jual per merchant. Punya resep (product_materials) yang dikurangi stoknya saat transaksi');
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')->nullOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('selling_price')->default(0);
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
