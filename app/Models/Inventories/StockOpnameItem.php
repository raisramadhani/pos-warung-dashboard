<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\StockOpnameType;
use Database\Factories\Inventories\StockOpnameItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stock_opname_id
 * @property int $item_id
 * @property float $system_quantity
 * @property float|null $actual_quantity
 * @property float|null $difference
 * @property string|null $notes
 * @property StockOpnameType $action_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Item|null $item
 * @property-read StockOpname|null $stockOpname
 *
 * @method static \Database\Factories\Inventories\StockOpnameItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem query()
 *
 * @mixin \Eloquent
 */
class StockOpnameItem extends Model
{
    /** @use HasFactory<StockOpnameItemFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'notes',
        'action_type',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'float',
            'actual_quantity' => 'float',
            'difference' => 'float',
            'action_type' => StockOpnameType::class,
        ];
    }

    /** @return BelongsTo<StockOpname, $this> */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
