<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail aktivitas (spatie/laravel-activitylog).
     * Mencatat siapa (causer), kapan, dan apa yang berubah (attribute_changes) pada model (subject).
     */
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->comment('Audit trail aktivitas (spatie/laravel-activitylog). Mencatat siapa, kapan, dan apa yang berubah pada model');
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->jsonb('attribute_changes')->nullable();
            $table->jsonb('properties')->nullable();
            $table->uuid('batch_uuid')->nullable()->index();
            $table->bigInteger('tenant_id')->nullable();
            $table->timestamps();
        });
    }
};
