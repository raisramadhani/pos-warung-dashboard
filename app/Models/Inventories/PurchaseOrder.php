<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\PurchaseOrderObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Purchase Order mencatat pemesanan barang ke supplier.
 *
 * Alur:
 * 1. SPV membuat PO → status = Approved (auto, ke depannya oleh accounting).
 * 2. Saat barang datang, SPV klik "Terima Barang" di halaman detail PO
 *    → goods receipt dibuat (bisa parsial, satu PO bisa banyak goods receipt).
 * 3. SPV merubah status  goods receipt menjadi verifikasi maka data tersimpan ke stok.
 *    → Per item (quantity_received) stok masuk ke merchant_stocks (bahan baku) atau asset dibuat (alat).
 * 4. Semua item sudah diterima lengkap → status otomatis Finished.
 *
 * Jumlah diterima per item dihitung dari goods_receipt_items (single source of truth),
 * bukan disimpan di purchase_order_items.
 *
 * Deferred Bug Ditangguhkan seharusnya disini ada ordered_at jaga-jaga kedepannya bisa custom tanggal PO
 *
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $supplier_id
 * @property string $po_number
 * @property PurchaseOrderSource $source_type
 * @property PurchaseOrderStatus $status
 * @property string|null $notes
 * @property Carbon|null $approved_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read User|null $creator
 * @property-read bool $is_complete
 * @property-read Collection<int, GoodsReceipt> $goodsReceipts
 * @property-read int|null $goods_receipts_count
 * @property-read bool|null $goods_receipts_exists
 * @property-read Collection<int, PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read Supplier|null $supplier
 *
 * @method static \Database\Factories\Inventories\PurchaseOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(PurchaseOrderObserver::class)]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'merchant_id',
        'supplier_id',
        'po_number',
        // Seharusnya source_type hanya ada di Good Reciept, tapi yauda tak apa, yang penting di view tidak perlu tampil.
        // Deferred Bug Ditangguhkan
        'source_type',
        'status',
        'notes',
        'approved_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => PurchaseOrderSource::class,
            'status' => PurchaseOrderStatus::class,
            'approved_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * Whether all items in this PO have been fully received.
     */
    public function getIsCompleteAttribute(): bool
    {
        $this->loadMissing('items');

        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (! $item->is_complete) {
                return false;
            }
        }

        return true;
    }
}
