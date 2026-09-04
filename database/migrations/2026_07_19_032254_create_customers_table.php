<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
