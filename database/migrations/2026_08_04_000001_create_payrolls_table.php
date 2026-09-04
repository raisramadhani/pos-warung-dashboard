<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            // Merchant tempat payroll dibuat (nullable karena bisa dihapus)
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete()
                ->comment('Merchant tempat payroll dibuat');
            // Karyawan (user) yang menerima gaji
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Karyawan yang menerima gaji');
            // Periode penggajian
            $table->date('period_start')->comment('Awal periode penggajian');
            $table->date('period_end')->comment('Akhir periode penggajian');
            // Status payroll: draft, approved, paid, dst.
            $table->string('status')->default('draft')->comment('Status payroll: draft, approved, paid, dst.');
            // Total gaji (dalam satuan terkecil, mis. rupiah tanpa desimal).
            // Signed agar bisa negatif (potongan gaji mengurangi total).
            $table->bigInteger('total_amount')->default(0)->comment('Total gaji dalam satuan terkecil (bisa negatif jika ada potongan)');
            // Target transaksi harian untuk bonus
            $table->unsignedBigInteger('bonus_target')->default(400)->comment('Target transaksi harian (cup)');
            // Bonus dasar per orang per hari
            $table->unsignedBigInteger('bonus_base_amount')->default(5000)->comment('Bonus dasar per orang per hari');
            // Konfigurasi kelipatan bonus
            $table->json('bonus_tiers')->nullable()->comment('Konfigurasi kelipatan bonus: [{step, amount}, ...]');
            // Catatan tambahan payroll
            $table->text('notes')->nullable()->comment('Catatan tambahan payroll');
            // User yang membuat payroll
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang membuat payroll');
            $table->timestamps();
            $table->softDeletes();

            // Index untuk lookup payroll per merchant per periode
            $table->index(['merchant_id', 'period_start']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
