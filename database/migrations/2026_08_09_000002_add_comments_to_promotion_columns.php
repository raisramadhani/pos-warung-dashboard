<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan komentar pada kolom promo yang direname, agar dokumentasi
     * skema konsisten dengan konvensi (setiap kolom punya ->comment()).
     */
    public function up(): void
    {
        Schema::table('promotion_conditions', function (Blueprint $table): void {
            $table->unsignedInteger('min_quantity')
                ->default(1)
                ->comment('Jumlah minimum produk yang harus dibeli agar promo berlaku')
                ->change();
        });

        Schema::table('promotion_rewards', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')
                ->nullable()
                ->comment('Untuk free_item: jumlah item gratis. Untuk fixed_price: jumlah item yang harganya di-override')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('promotion_conditions', function (Blueprint $table): void {
            $table->unsignedInteger('min_quantity')->default(1)->change();
        });

        Schema::table('promotion_rewards', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->nullable()->change();
        });
    }
};
