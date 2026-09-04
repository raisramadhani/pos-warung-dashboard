<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Merchant/outlet dan gudang.
     * type=warehouse untuk gudang pusat, type=merchant untuk outlet.
     * Status dikelola via spatie/laravel-model-status (hybrid strategy).
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->comment('Merchant/outlet dan gudang. type=warehouse untuk gudang pusat, type=merchant untuk outlet. Status dikelola via spatie/laravel-model-status');
            $table->id();
            $table->string('name');
            $table->string('type')->default(MerchantType::Merchant->value)->comment('Tipe merchant: warehouse (gudang) atau merchant (outlet)');
            $table->string('slug')->unique();
            $table->string('avatar_path')->nullable();
            $table->text('address')->nullable();
            $table->string('current_status')->default(MerchantStatus::Inactive->value);
            $table->string('ownership_type')->default('main')->comment('Tipe kepemilikan: main (pusat) atau branch (cabang)');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('merchant_user', function (Blueprint $table) {
            $table->comment('Pivot: relasi many-to-many antara merchant dan user (anggota merchant)');
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
