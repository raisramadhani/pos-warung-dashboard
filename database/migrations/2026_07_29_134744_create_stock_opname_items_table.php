<?php

use App\Enums\Inventories\StockOpnameType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opnames')
                ->cascadeOnDelete();
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();
            $table->decimal('system_quantity', 10, 4);
            $table->decimal('actual_quantity', 10, 4)->nullable();
            $table->decimal('difference', 10, 4)->nullable();
            $table->text('notes')->nullable();
            $table->string('action_type')
                ->default(StockOpnameType::Adjustment->value);

            $table->timestamps();

            $table->unique(['stock_opname_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
