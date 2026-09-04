<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data item/bahan (satuan terkecil).
     * type=raw_material atau tool. Digunakan di PO, distribusi, resep produk, dan stok merchant.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->comment('Master data item/bahan (satuan terkecil). type=raw_material atau tool. Digunakan di PO, distribusi, resep produk, dan stok merchant');
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('unit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
