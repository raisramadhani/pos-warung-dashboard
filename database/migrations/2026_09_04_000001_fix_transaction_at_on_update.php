<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki kolom transaction_at agar tidak kena "ON UPDATE CURRENT_TIMESTAMP" implisit MySQL.
     *
     * Sebelumnya transaction_at dideklarasikan sebagai TIMESTAMP NOT NULL pertama tanpa DEFAULT,
     * sehingga MySQL diam-diam menambahkan "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP".
     * Akibatnya setiap UPDATE pada baris transaksi (mis. update items_count oleh
     * TransactionItemObserver) menimpa transaction_at ke waktu sekarang — semua transaksi hasil
     * seed tampak jatuh di tanggal yang sama. Kolom dibuat nullable agar perilaku implisit
     * tersebut hilang; TransactionObserver::creating() tetap mengisi transaction_at = now()
     * bila kosong.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->timestamp('transaction_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->timestamp('transaction_at')->nullable(false)->change();
        });
    }
};
