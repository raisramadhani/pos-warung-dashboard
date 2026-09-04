<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master promo per merchant.
     * Promo bekerja dengan pola "syarat terpenuhi → hadiah diberikan" dalam jendela waktu
     * (promotion_schedules). Tipe promo diatur lewat kolom type (bundle, buy_x_get_y,
     * flash sale, persen, atau nominal).
     *
     * Guard hasTable(): di lingkungan lama tabel sudah ada (migrasi awal dihapus),
     * migrasi ini dilewati agar tidak konflik.
     */
    public function up(): void
    {
        if (Schema::hasTable('promotions')) {
            return;
        }

        Schema::create('promotions', function (Blueprint $table): void {
            $table->comment('Master promo per merchant. Promo bekerja dengan pola syarat terpenuhi → hadiah diberikan dalam jendela waktu (promotion_schedules)');
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete()
                ->comment('Merchant pemilik promo. NULL = promo global (belum dipakai)');
            $table->string('name')
                ->comment('Nama promo, mis. Jumat Berkah, Buy 2 Get 1');
            $table->string('slug')
                ->comment('Slug unik promo, dibuat otomatis dari nama');
            $table->text('description')
                ->nullable()
                ->comment('Deskripsi atau keterangan tambahan promo');
            $table->string('type')
                ->comment('Tipe promo: bundle_fixed_price, buy_x_get_y, flash_sale_price, percent_discount, fixed_discount');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Status aktif promo. Nonaktif tidak pernah dievaluasi');
            $table->timestamp('starts_at')
                ->nullable()
                ->comment('Tanggal mulai berlaku promo. NULL = berlaku sejak kapan saja');
            $table->timestamp('ends_at')
                ->nullable()
                ->comment('Tanggal berakhir promo. NULL = berlaku selamanya');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
