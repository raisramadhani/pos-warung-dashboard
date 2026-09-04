<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail item dalam transaksi.
     * product_data menyimpan snapshot lengkap produk saat transaksi (harga/nama/komposisi bisa berubah di masa depan).
     * original_price menyimpan harga sebelum promo, discount_amount total diskon per baris,
     * dan promotion_id menautkan baris ke promo yang menghasilkan diskon (tabel promotions
     * dibuat sebelum tabel ini agar FK valid). Baris item gratis (unit_price = 0) tetap
     * tercatat dengan promotion_id terisi.
     */
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table): void {
            $table->comment('Detail item dalam transaksi. product_data menyimpan snapshot lengkap produk saat transaksi (harga/nama/komposisi bisa berubah)');
            $table->id();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
            $table->jsonb('product_data')
                ->nullable()
                ->comment('Snapshot lengkap produk saat transaksi: seluruh kolom produk + relasi category + product_materials[].item (komposisi/raw materials). Hasil Product::with(["category","productMaterials.item"])->toArray()');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price')
                ->comment('Harga satuan produk. Pada promo diskon/bundle tetap harga normal produk; pada item gratis = 0; pada flash sale = harga flash sale.');
            $table->unsignedBigInteger('original_price')
                ->nullable()
                ->comment('Harga satuan sebelum promo diterapkan');
            $table->unsignedBigInteger('discount_amount')
                ->default(0)
                ->comment('Total nominal diskon/potongan promo yang diterapkan pada baris ini');
            $table->foreignId('promotion_id')
                ->nullable()
                ->constrained('promotions')
                ->nullOnDelete()
                ->comment('Promo yang menghasilkan diskon pada baris ini. NULL = tanpa promo');
            $table->jsonb('promotion_data')
                ->nullable()
                ->comment('Snapshot promo yang diterapkan pada baris ini (nama, tipe, syarat, hadiah). NULL = tanpa promo. Hasil Promotion::toSnapshot()');
            $table->unsignedBigInteger('subtotal')
                ->comment('Subtotal baris = (quantity * unit_price) - discount_amount. Pada item gratis subtotal = 0.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
