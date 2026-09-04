<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User yang dijadwalkan');
            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->cascadeOnDelete()
                ->comment('Merchant tempat bertugas');
            $table->date('date')->comment('Tanggal shift');
            $table->time('start_time')->comment('Jam mulai');
            $table->time('end_time')->comment('Jam selesai');
            $table->text('notes')->nullable()->comment('Catatan opsional');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'date']);
            $table->index(['merchant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_schedules');
    }
};
