<?php

namespace App\Models\Products;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\TransactionItem;
use App\Traits\ActivityLogs;
use Database\Factories\Products\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $image_path
 * @property int $selling_price
 * @property int $cost_price
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Category|null $category
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, ProductMaterial> $productMaterials
 * @property-read int|null $product_materials_count
 * @property-read bool|null $product_materials_exists
 * @property-read Collection<int, TransactionItem> $transactionItems
 * @property-read int|null $transaction_items_count
 * @property-read bool|null $transaction_items_exists
 *
 * @method static \Database\Factories\Products\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use ActivityLogs, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'category_id',
        'name',
        'slug',
        'image_path',
        'selling_price',
        'cost_price',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'integer',
            'cost_price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productMaterials(): HasMany
    {
        return $this->hasMany(ProductMaterial::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->where('merchant_id', $this->merchant_id))
            ->doNotGenerateSlugsOnUpdate();
    }
}
