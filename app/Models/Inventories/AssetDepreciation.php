<?php

namespace App\Models\Inventories;

use Database\Factories\Inventories\AssetDepreciationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $asset_id
 * @property Carbon $period_date
 * @property numeric $depreciation_amount
 * @property numeric $book_value_before
 * @property numeric $book_value_after
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Asset|null $asset
 *
 * @method static \Database\Factories\Inventories\AssetDepreciationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation query()
 *
 * @mixin \Eloquent
 */
class AssetDepreciation extends Model
{
    /** @use HasFactory<AssetDepreciationFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'period_date',
        'depreciation_amount',
        'book_value_before',
        'book_value_after',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'depreciation_amount' => 'decimal:2',
            'book_value_before' => 'decimal:2',
            'book_value_after' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
