<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi sistem (Laravel Notifications).
     * Polymorphic: bisa dikirim ke user atau model lain. Data payload dalam format JSON.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->comment('Notifikasi sistem (Laravel Notifications). Polymorphic: bisa dikirim ke user atau model lain');
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
