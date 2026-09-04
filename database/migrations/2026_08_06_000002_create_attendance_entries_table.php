<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            // Daftar kehadiran induk (hapus sheet = hapus entries)
            $table->foreignId('attendance_sheet_id')
                ->constrained('attendance_sheets')
                ->cascadeOnDelete()
                ->comment('Daftar kehadiran induk');
            // Merchant/cabang
            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->cascadeOnDelete()
                ->comment('Merchant/cabang');
            // Tanggal kehadiran
            $table->date('date')->comment('Tanggal kehadiran');
            // Jumlah karyawan masuk
            $table->unsignedInteger('employee_count')->default(0)->comment('Jumlah karyawan masuk');
            $table->timestamps();

            // Unik per sheet + merchant + date
            $table->unique(['attendance_sheet_id', 'merchant_id', 'date']);
            // Index untuk query per merchant
            $table->index(['merchant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_entries');
    }
};
