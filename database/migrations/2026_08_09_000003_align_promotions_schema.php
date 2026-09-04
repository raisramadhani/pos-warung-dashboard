<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyelaraskan skema tabel promo di lingkungan lama dengan desain final:
     * - promotions: menambah kolom slug (unik per merchant) + backfill data existing
     * - promotion_schedules: menambah index (promotion_id, day_of_week)
     *
     * Di lingkungan baru (fresh migrate) semua kolom sudah ada, migrasi ini no-op.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('promotions', 'slug')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->string('slug')
                    ->nullable()
                    ->after('name')
                    ->comment('Slug unik promo, dibuat otomatis dari nama');
            });

            // Backfill slug untuk data existing.
            DB::table('promotions')->orderBy('id')->each(function (object $promotion): void {
                $base = str($promotion->name)->slug()->toString();
                $slug = $base;
                $counter = 2;

                while (DB::table('promotions')
                    ->where('merchant_id', $promotion->merchant_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $promotion->id)
                    ->exists()) {
                    $slug = $base.'-'.$counter;
                    $counter++;
                }

                DB::table('promotions')
                    ->where('id', $promotion->id)
                    ->update(['slug' => $slug]);
            });

            Schema::table('promotions', function (Blueprint $table): void {
                $table->string('slug')->nullable(false)->change();
                $table->unique(['merchant_id', 'slug']);
            });
        }

        Schema::table('promotion_schedules', function (Blueprint $table): void {
            $hasIndex = collect(Schema::getIndexes('promotion_schedules'))
                ->contains(fn (array $index): bool => $index['name'] === 'promotion_schedules_promotion_id_day_of_week_index');

            if (! $hasIndex) {
                $table->index(['promotion_id', 'day_of_week']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropUnique(['merchant_id', 'slug']);
            $table->dropColumn('slug');
        });

        Schema::table('promotion_schedules', function (Blueprint $table): void {
            $table->dropIndex(['promotion_id', 'day_of_week']);
        });
    }
};
