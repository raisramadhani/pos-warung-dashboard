<?php

use App\Enums\Inventories\ReceiptSourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penerimaan barang dari supplier/PO.
     * Saat diverifikasi, stok gudang bertambah (RawMaterial) atau aset dibuat (Tool) oleh GoodsReceiptObserver.
     */
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->comment('Penerimaan barang dari supplier/PO. Saat diverifikasi, stok gudang bertambah (RawMaterial) atau aset dibuat (Tool)');
            $table->id();
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();
            $table->string('receipt_number')->unique();
            $table->string('source_type')->default(ReceiptSourceType::Purchasing->value);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
