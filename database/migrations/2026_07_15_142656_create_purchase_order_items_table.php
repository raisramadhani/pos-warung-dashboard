<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail item dalam purchase order.
     * Mencatat item yang dipesan, jumlah, dan harga satuan dari supplier.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->comment('Detail item dalam purchase order: item yang dipesan, jumlah, dan harga satuan');
            $table->id();
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();
            $table->decimal('quantity_ordered', 10, 4)->default(0);
            $table->decimal('unit_price_ordered', 15, 2)->default(0);
            $table->decimal('subtotal_ordered', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
