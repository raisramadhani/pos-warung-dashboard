<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyamakan penamaan kolom promo dengan konvensi nama full.
     * Tabel promo (promotions, promotion_schedules, promotion_conditions,
     * promotion_rewards, promotion_redemptions) sudah dibuat pada migrasi awal
     * dengan nama kolom singkatan; migrasi ini merename kolom tanpa kehilangan data.
     */
    public function up(): void
    {
        if (Schema::hasColumn('promotion_conditions', 'min_qty')) {
            Schema::table('promotion_conditions', function (Blueprint $table): void {
                $table->renameColumn('min_qty', 'min_quantity');
            });
        }

        if (Schema::hasColumn('promotion_rewards', 'qty')) {
            Schema::table('promotion_rewards', function (Blueprint $table): void {
                $table->renameColumn('qty', 'quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('promotion_conditions', 'min_quantity')) {
            Schema::table('promotion_conditions', function (Blueprint $table): void {
                $table->renameColumn('min_quantity', 'min_qty');
            });
        }

        if (Schema::hasColumn('promotion_rewards', 'quantity')) {
            Schema::table('promotion_rewards', function (Blueprint $table): void {
                $table->renameColumn('quantity', 'qty');
            });
        }
    }
};
