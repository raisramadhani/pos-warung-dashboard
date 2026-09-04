<?php

namespace App\Models\Promotions;

use App\Enums\Promotions\PromotionRewardType;
use App\Models\Products\Product;
use Database\Factories\Promotions\PromotionRewardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property PromotionRewardType $reward_type Jenis hadiah: free_item, fixed_price, percent_discount, fixed_discount
 * @property int|null $product_id
 * @property int|null $quantity Untuk free_item: jumlah item gratis. Untuk fixed_price: jumlah item yang harganya di-override
 * @property int|null $value fixed_price → harga baru; percent_discount → persen diskon; fixed_discount → nominal potongan; free_item → NULL
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product|null $product
 * @property-read Promotion|null $promotion
 *
 * @method static \Database\Factories\Promotions\PromotionRewardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionReward newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionReward newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionReward query()
 *
 * @mixin \Eloquent
 */
class PromotionReward extends Model
{
    /** @use HasFactory<PromotionRewardFactory> */
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'reward_type',
        'product_id',
        'quantity',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'reward_type' => PromotionRewardType::class,
            'quantity' => 'integer',
            'value' => 'integer',
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
}
