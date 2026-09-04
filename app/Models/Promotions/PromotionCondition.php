<?php

namespace App\Models\Promotions;

use App\Models\Category;
use App\Models\Products\Product;
use Database\Factories\Promotions\PromotionConditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int|null $product_id
 * @property int|null $category_id
 * @property int $min_quantity Jumlah minimum produk yang harus dibeli agar promo berlaku
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $category
 * @property-read Product|null $product
 * @property-read Promotion|null $promotion
 *
 * @method static \Database\Factories\Promotions\PromotionConditionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionCondition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionCondition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionCondition query()
 *
 * @mixin \Eloquent
 */
class PromotionCondition extends Model
{
    /** @use HasFactory<PromotionConditionFactory> */
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'category_id',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
