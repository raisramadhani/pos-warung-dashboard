<?php

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
        Schema::create('cash_drawer_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();
            $table->string('shift_number')->unique();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('opening_amount')->default(0);
            $table->text('opening_note')->nullable();
            $table->unsignedBigInteger('expected_cash_amount')->nullable();
            $table->unsignedBigInteger('declared_cash_amount')->nullable();
            $table->bigInteger('difference')->nullable();
            $table->foreignId('opened_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_shifts');
    }
};
