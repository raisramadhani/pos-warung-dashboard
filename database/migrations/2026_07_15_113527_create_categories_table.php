<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori produk per merchant.
     * Satu merchant bisa punya kategori sendiri (merchant_id nullable untuk global).
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->comment('Kategori produk per merchant. Satu merchant bisa punya kategori sendiri (merchant_id nullable untuk global)');
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 250);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['merchant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
