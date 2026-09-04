<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Observers\GoodsReceiptObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Goods Receipt mencatat setiap event penerimaan barang dari supplier.
 *
 * Setiap pengiriman dari supplier (bisa parsial) dibuat sebagai goods receipt
 * terpisah yang terkait ke purchase order. Saat goods receipt diverifikasi,
 * stok langsung masuk ke merchant_stocks (untuk bahan baku) atau asset
 * record dibuat (untuk alat).
 *
 * @property int $id
 * @property int|null $purchase_order_id
 * @property int|null $merchant_id
 * @property string $receipt_number
 * @property ReceiptSourceType $source_type
 * @property GoodsReceiptStatus $status
 * @property string|null $notes
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, GoodsReceiptItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Collection<int, GoodsReceiptItem> $verifiedItems
 * @property-read int|null $verified_items_count
 * @property-read bool|null $verified_items_exists
 *
 * @method static \Database\Factories\Inventories\GoodsReceiptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(GoodsReceiptObserver::class)]
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'purchase_order_id',
        'merchant_id',
        'receipt_number',
        'source_type',
        'status',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ReceiptSourceType::class,
            'status' => GoodsReceiptStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * Item yang sudah diverifikasi (quantity_received > 0).
     */
    public function verifiedItems(): HasMany
    {
        return $this->items()->where('quantity_received', '>', 0);
    }
}
