<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distribusi barang dari gudang/merchant ke merchant tujuan.
     * Status: Sent→Finished. Stok dikurangi saat Sent (source), ditambah saat Finished (destination).
     */
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->comment('Distribusi barang dari gudang/merchant ke merchant tujuan. Status: Sent→Finished. Stok dikurangi saat Sent (source), ditambah saat Finished (destination)');
            $table->id();
            $table->foreignId('source_merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained()->nullOnDelete();
            $table->string('status')->default('dikirim');
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
