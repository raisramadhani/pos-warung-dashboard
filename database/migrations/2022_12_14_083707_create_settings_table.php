<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application settings (spatie/laravel-settings).
     * Menyimpan pengaturan aplikasi dalam format JSON per group.
     */
    public function up(): void
    {
        Schema::create(config('settings.repositories.database.table') ?? 'settings', function (Blueprint $table): void {
            $table->comment('Application settings (spatie/laravel-settings). Menyimpan pengaturan aplikasi dalam format JSON');
            $table->id();

            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->jsonb('payload');

            $table->timestamps();

            $table->unique(['group', 'name']);
        });
    }
};
