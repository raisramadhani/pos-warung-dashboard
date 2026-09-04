<?php

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase order: pengadaan barang dari supplier.
     * source_type menentukan asal (Purchasing/Donation/Opening/Return).
     * Status via observer: Draft→Approved→Finished.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->comment('Purchase order: pengadaan barang dari supplier. source_type menentukan asal (Purchasing/Donation/Opening/Return). Status via observer: Draft→Approved→Finished');
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->string('po_number')->unique();
            $table->string('source_type')->default(PurchaseOrderSource::Purchasing->value);
            $table->string('status')->default(PurchaseOrderStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
