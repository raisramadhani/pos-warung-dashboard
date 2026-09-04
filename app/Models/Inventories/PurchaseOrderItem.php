<?php

namespace App\Models\Inventories;

use Database\Factories\Inventories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $purchase_order_id
 * @property int|null $item_id
 * @property float $quantity_ordered
 * @property numeric $unit_price_ordered
 * @property numeric $subtotal_ordered
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read bool $is_complete
 * @property-read float $quantity_received
 * @property-read float $quantity_remaining
 * @property-read Item|null $item
 * @property-read PurchaseOrder|null $purchaseOrder
 *
 * @method static \Database\Factories\Inventories\PurchaseOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'quantity_ordered',
        'unit_price_ordered',
        'subtotal_ordered',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'float',
            'unit_price_ordered' => 'decimal:2',
            'subtotal_ordered' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Total quantity received across all goods receipts for this PO item.
     */
    public function getQuantityReceivedAttribute(): float
    {
        return (float) GoodsReceiptItem::whereHas('goodsReceipt', function ($query) {
            $query->where('purchase_order_id', $this->purchase_order_id);
        })
            ->where('item_id', $this->item_id)
            ->sum('quantity_received');
    }

    /**
     * Remaining quantity not yet received.
     */
    public function getQuantityRemainingAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    /**
     * Whether this item has been fully received.
     */
    public function getIsCompleteAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }
}
