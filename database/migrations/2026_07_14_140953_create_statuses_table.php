<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Riwayat perubahan status model (spatie/laravel-model-status).
     * Polymorphic: bisa untuk merchant, PO, distribusi, dan model lain.
     * Actor mencatat siapa yang mengubah status.
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->comment('Riwayat perubahan status model (spatie/laravel-model-status). Polymorphic: bisa untuk merchant, PO, distribusi, dll');
            $table->increments('id');
            $table->string('name');
            $table->text('reason')->nullable();
            $table->morphs('model');
            $table->nullableMorphs('actor');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('statuses');
    }
}
