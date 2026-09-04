<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            // Payroll induk (hapus payroll = hapus item)
            $table->foreignId('payroll_id')
                ->constrained('payrolls')
                ->cascadeOnDelete()
                ->comment('Payroll induk (hapus payroll = hapus item)');
            // Nama komponen gaji, mis. "Gaji Pokok", "Tunjangan Makan"
            $table->string('component_name')->comment('Nama komponen gaji, mis. Gaji Pokok, Tunjangan Makan');
            // Tarif harian komponen. Signed agar bisa negatif (potongan gaji).
            $table->bigInteger('daily_rate')->comment('Tarif harian komponen (bisa negatif untuk potongan)');
            // Jumlah hari kerja. Signed agar bisa negatif.
            $table->integer('days')->default(0)->comment('Jumlah hari kerja (bisa negatif untuk potongan)');
            // Total = daily_rate * days (disimpan agar konsisten). Signed agar bisa negatif.
            $table->bigInteger('amount')->default(0)->comment('Total = daily_rate * days (bisa negatif untuk potongan)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
