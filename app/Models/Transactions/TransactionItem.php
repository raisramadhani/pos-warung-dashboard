<?php

namespace App\Models\Transactions;

use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use App\Observers\TransactionItemObserver;
use Database\Factories\Transactions\TransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $transaction_id
 * @property int|null $product_id
 * @property array<array-key, mixed>|null $product_data Snapshot lengkap produk saat transaksi: seluruh kolom produk + relasi category + product_materials[].item (komposisi/raw materials). Hasil Product::with(["category","productMaterials.item"])->toArray()
 * @property int $quantity
 * @property int $unit_price Harga satuan setelah promo diterapkan. Item gratis = 0. Harga sebelum promo disimpan di original_price
 * @property int|null $original_price Harga satuan sebelum promo diterapkan
 * @property int $discount_amount Total diskon yang diterapkan pada baris ini
 * @property int|null $promotion_id
 * @property array<array-key, mixed>|null $promotion_data Snapshot promo yang diterapkan pada baris ini (nama, tipe, syarat, hadiah). NULL = tanpa promo. Hasil Promotion::toSnapshot()
 * @property int $subtotal Subtotal baris = quantity * unit_price (unit_price sudah harga setelah promo; item gratis unit_price=0 → subtotal 0).
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product|null $product
 * @property-read Promotion|null $promotion
 * @property-read Transaction|null $transaction
 *
 * @method static \Database\Factories\Transactions\TransactionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(TransactionItemObserver::class)]
class TransactionItem extends Model
{
    /** @use HasFactory<TransactionItemFactory> */
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        // Hati-hati saat merubah struktur product_data. Karena di data ini sudah digunakan di laporan laba rugi, maka pastikan perubahan struktur tidak merusak perhitungan laba rugi.
        // product_data berisi snapshot lengkap produk BESERTA komposisinya (resep) pada saat
        // transaksi dibuat, hasil dari Product::with(['category', 'productMaterials.item'])->get()->toArray().
        // Struktur: seluruh kolom produk (id, name, slug, selling_price, cost_price, dst.) + relasi `category`
        // (nested) + `product_materials` (array; tiap elemen memuat id, product_id, item_id, quantity_required,
        // dan `item` yang menampung data item bahan baku: id, name, type, unit, dst.). Karena produk & komposisi
        // sudah ter-snapshot, jangan ubah struktur ini tanpa menyesuaikan laporan laba rugi.
        'product_data',
        'quantity',
        'unit_price',
        'original_price',

        /** @var int $discount_amount
         * Disengaja redundan dengan promotion_redemptions.discount_amount.
         * Sehingga nilai yang tampil di sini harus sama dengan nilai yang tampil di promotion_redemptions.discount_amount.
         */
        'discount_amount',
        'promotion_id',
        'promotion_data',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'product_data' => 'array',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'original_price' => 'integer',
            'discount_amount' => 'integer',
            'promotion_data' => 'array',
            'subtotal' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Snapshot produk sebagai array (PHPStan tidak bisa menyimpulkan tipe array
     * dari cast JSON kolom). Mengembalikan array kosong bila snapshot null.
     *
     * @return array<string, mixed>
     */
    public function productData(): array
    {
        return $this->product_data ?? [];
    }

    /**
     * Snapshot promo sebagai array (PHPStan tidak bisa menyimpulkan tipe array
     * dari cast JSON kolom). Mengembalikan array kosong bila snapshot null.
     *
     * @return array<string, mixed>
     */
    public function promotionData(): array
    {
        return $this->promotion_data ?? [];
    }
}
