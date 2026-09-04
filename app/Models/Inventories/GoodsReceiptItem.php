<?php

namespace App\Models\Inventories;

use Database\Factories\Inventories\GoodsReceiptItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $goods_receipt_id
 * @property int|null $item_id
 * @property float|null $quantity_ordered
 * @property float $quantity_received
 * @property numeric|null $unit_price
 * @property numeric|null $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read GoodsReceipt|null $goodsReceipt
 * @property-read Item|null $item
 *
 * @method static \Database\Factories\Inventories\GoodsReceiptItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem withoutTrashed()
 *
 * @mixin \Eloquent
 */
class GoodsReceiptItem extends Model
{
    /** @use HasFactory<GoodsReceiptItemFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id',
        'item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'float',
            'quantity_received' => 'float',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
