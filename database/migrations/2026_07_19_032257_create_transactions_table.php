<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaksi penjualan di merchant.
     * Saat dibuat, stok bahan baku otomatis dikurangi via TransactionItemObserver.
     * subtotal adalah total dari amount pada transaction_items,
     * discount adalah total dari diskon yang diberikan pada transaction_items,
     * total_amount (grandtotal) = subtotal - discount.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->comment('Transaksi penjualan di merchant. Saat dibuat, stok bahan baku otomatis dikurangi via TransactionItemObserver');
            $table->id();
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('transaction_number')->unique();
            $table->string('idempotency_key')
                ->nullable()
                ->comment('UUID unik per checkout untuk mencegah transaksi duplikat (idempotency)');
            $table->string('payment_method');
            $table->unsignedBigInteger('subtotal')
                ->comment('Total dari amount pada transaction_items');
            $table->unsignedBigInteger('discount')
                ->default(0)
                ->comment('Total dari diskon yang diberikan pada transaction_items');
            $table->unsignedBigInteger('total_amount')
                ->comment('Grandtotal transaksi = subtotal - discount');
            $table->unsignedBigInteger('amount_received')
                ->nullable()
                ->comment('Uang yang diterima dari pelanggan (cash) atau total (qris)');
            $table->unsignedBigInteger('change')
                ->nullable()
                ->comment('Kembalian yang diberikan = amount_received - total_amount');
            $table->unsignedInteger('items_count')
                ->default(0)
                ->comment('Total jumlah item yang terjual');
            $table->text('notes')->nullable();
            $table->timestamp('transaction_at');
            $table->timestamps();

            $table->unique(['merchant_id', 'idempotency_key']);
            $table->index(['merchant_id', 'transaction_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
