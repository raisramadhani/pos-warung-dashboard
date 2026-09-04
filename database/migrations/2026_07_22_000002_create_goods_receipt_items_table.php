<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail item penerimaan barang.
     * quantity_received = jumlah aktual diterima (bisa berbeda dari quantity_ordered di PO).
     */
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->comment('Detail item penerimaan barang. quantity_received = jumlah aktual diterima (bisa berbeda dari quantity_ordered)');
            $table->id();
            $table->foreignId('goods_receipt_id')
                ->nullable()
                ->constrained('goods_receipts')
                ->nullOnDelete();
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();
            $table->decimal('quantity_ordered', 10, 4)->nullable();
            $table->decimal('quantity_received', 10, 4)->default(0);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
