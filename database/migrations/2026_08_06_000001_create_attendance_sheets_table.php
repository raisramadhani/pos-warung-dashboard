<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table) {
            $table->id();
            // Tipe periode: monthly atau weekly
            $table->string('period_type')->comment('Tipe periode: monthly atau weekly');
            // Rentang tanggal periode kehadiran
            $table->date('date_from')->comment('Tanggal awal periode kehadiran');
            $table->date('date_to')->comment('Tanggal akhir periode kehadiran');
            // Catatan tambahan
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->timestamps();
            $table->softDeletes();

            // Index untuk lookup berdasarkan tipe dan rentang tanggal
            $table->index(['period_type', 'date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sheets');
    }
};
