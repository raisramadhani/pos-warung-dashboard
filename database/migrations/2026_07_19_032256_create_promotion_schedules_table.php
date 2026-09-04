<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jendela waktu aktif sebuah promo.
     * Satu promo bisa punya beberapa jendela (mis. flash sale pagi & malam).
     * Jika tidak ada baris di tabel ini, promo berlaku kapan saja.
     * Jika end_time < start_time, jendela dianggap lintas tengah malam
     * (mis. 22:00–00:00 berarti aktif mulai jam 22:00 hingga tengah malam).
     *
     * Guard hasTable(): di lingkungan lama tabel sudah ada (migrasi awal dihapus),
     * migrasi ini dilewati agar tidak konflik.
     */
    public function up(): void
    {
        if (Schema::hasTable('promotion_schedules')) {
            return;
        }

        Schema::create('promotion_schedules', function (Blueprint $table): void {
            $table->comment('Jendela waktu aktif sebuah promo. Satu promo bisa punya beberapa jendela (mis. flash sale pagi & malam)');
            $table->id();
            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete()
                ->comment('Promo pemilik jendela waktu');
            $table->unsignedTinyInteger('day_of_week')
                ->nullable()
                ->comment('Hari dalam seminggu (1=Senin sampai 7=Minggu). NULL = berlaku setiap hari');
            $table->time('start_time')
                ->nullable()
                ->comment('Jam mulai jendela promo. NULL = mulai dari awal hari');
            $table->time('end_time')
                ->nullable()
                ->comment('Jam selesai jendela promo. Jika end_time < start_time, jendela lintas tengah malam. NULL = sampai akhir hari');
            $table->timestamps();

            $table->index(['promotion_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_schedules');
    }
};
