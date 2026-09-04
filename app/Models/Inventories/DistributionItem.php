<?php

namespace App\Models\Inventories;

use App\Observers\DistributionItemObserver;
use Database\Factories\Inventories\DistributionItemFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $distribution_id
 * @property int|null $item_id
 * @property float $quantity_sent
 * @property float $quantity_received
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Distribution|null $distribution
 * @property-read Item|null $item
 *
 * @method static \Database\Factories\Inventories\DistributionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(DistributionItemObserver::class)]
class DistributionItem extends Model
{
    /** @use HasFactory<DistributionItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distribution_id',
        'item_id',
        'quantity_sent',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity_sent' => 'float',
            'quantity_received' => 'float',
        ];
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
