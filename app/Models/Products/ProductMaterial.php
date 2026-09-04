<?php

namespace App\Models\Products;

use App\Models\Inventories\Item;
use Database\Factories\Products\ProductMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $product_id
 * @property int|null $item_id
 * @property float $quantity_required
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read Product|null $product
 *
 * @method static \Database\Factories\Products\ProductMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ProductMaterial extends Model
{
    /** @use HasFactory<ProductMaterialFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'item_id',
        'quantity_required',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
