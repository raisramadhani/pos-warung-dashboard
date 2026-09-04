<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->comment('Arus kas masuk/keluar per merchant. amount bertanda: minus untuk pengeluaran, plus untuk pemasukan');
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->comment('Merchant pemilik arus kas')
                ->constrained('merchants')
                ->nullOnDelete();
            $table->string('type')->default('expense')->comment('Jenis arus kas: income (pemasukan) atau expense (pengeluaran)');
            $table->text('description')->nullable()->comment('Keterangan opsional');
            $table->bigInteger('amount')->comment('Nominal bertanda: minus untuk pengeluaran, plus untuk pemasukan');
            $table->boolean('affects_cash_drawer')->default(true)->comment('Apakah nominal ini menambah/mengurangi saldo cashdrawer saat tutup shift');
            $table->date('transaction_date')->comment('Tanggal transaksi keuangan');
            $table->timestamps();

            $table->index(['merchant_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
