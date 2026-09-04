<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\ItemType;
use App\Models\Activity;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\ItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ItemType $type
 * @property string $unit
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, Asset> $assets
 * @property-read int|null $assets_count
 * @property-read bool|null $assets_exists
 * @property-read Collection<int, DistributionItem> $distributionItems
 * @property-read int|null $distribution_items_count
 * @property-read bool|null $distribution_items_exists
 * @property-read Collection<int, MerchantStock> $merchantStocks
 * @property-read int|null $merchant_stocks_count
 * @property-read bool|null $merchant_stocks_exists
 * @property-read Collection<int, PurchaseOrderItem> $purchaseOrderItems
 * @property-read int|null $purchase_order_items_count
 * @property-read bool|null $purchase_order_items_exists
 *
 * @method static \Database\Factories\Inventories\ItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use ActivityLogs, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'unit',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'type' => ItemType::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function merchantStocks(): HasMany
    {
        return $this->hasMany(MerchantStock::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function distributionItems(): HasMany
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
