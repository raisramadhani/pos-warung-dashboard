<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail mutasi stok (immutable).
     * Ditulis oleh StockMovementService setiap increase/decrease.
     * Tidak punya updated_at karena tidak boleh diedit.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->comment('Audit trail mutasi stok (immutable). Ditulis oleh StockMovementService setiap increase/decrease. Tidak punya updated_at karena tidak boleh diedit');
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 4); // positive = in, negative = out
            $table->decimal('quantity_before', 10, 4);
            $table->decimal('quantity_after', 10, 4);
            $table->string('type'); // StockMovementType enum value
            $table->nullableMorphs('reference'); // reference_type + reference_id
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable(); // no updated_at — immutable audit trail

            $table->index(['merchant_id', 'item_id']);
            $table->index(['type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
