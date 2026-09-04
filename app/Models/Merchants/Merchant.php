<?php

namespace App\Models\Merchants;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Models\Activity;
use App\Models\CashFlows\CashFlow;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\MerchantStock;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use App\Models\Status;
use App\Models\Transactions\Transaction;
use App\Models\User;
use App\Observers\MerchantObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Merchants\MerchantFactory;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property MerchantType $type Tipe merchant: warehouse (gudang) atau merchant (outlet)
 * @property string $slug
 * @property string|null $avatar_path
 * @property string|null $address
 * @property MerchantStatus $current_status
 * @property OwnershipType $ownership_type Tipe kepemilikan: main (pusat) atau branch (cabang)
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, CashFlow> $cashFlows
 * @property-read int|null $cash_flows_count
 * @property-read bool|null $cash_flows_exists
 * @property-read Collection<int, Distribution> $distributions
 * @property-read int|null $distributions_count
 * @property-read bool|null $distributions_exists
 * @property-read MerchantUser|null $pivot
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read bool|null $members_exists
 * @property-read Collection<int, MerchantStock> $merchantStocks
 * @property-read int|null $merchant_stocks_count
 * @property-read bool|null $merchant_stocks_exists
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read bool|null $products_exists
 * @property-read Collection<int, Promotion> $promotions
 * @property-read int|null $promotions_count
 * @property-read bool|null $promotions_exists
 * @property-read Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read bool|null $statuses_exists
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 *
 * @method static Builder<static>|Merchant currentStatus(...$names)
 * @method static \Database\Factories\Merchants\MerchantFactory factory($count = null, $state = [])
 * @method static Builder<static>|Merchant merchantsOnly()
 * @method static Builder<static>|Merchant newModelQuery()
 * @method static Builder<static>|Merchant newQuery()
 * @method static Builder<static>|Merchant onlyTrashed()
 * @method static Builder<static>|Merchant otherCurrentStatus(...$names)
 * @method static Builder<static>|Merchant query()
 * @method static Builder<static>|Merchant warehouses()
 * @method static Builder<static>|Merchant withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Merchant withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(MerchantObserver::class)]
class Merchant extends Model implements HasAvatar, HasName
{
    use ActivityLogs;

    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    use HasSlug;
    use HasStatuses {
        setStatus as spatieSetStatus;
    }
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'slug',
        'avatar_path',
        'address',
        'ownership_type',
        'latitude',
        'longitude',
        'current_status',
    ];

    protected $casts = [
        'type' => MerchantType::class,
        'current_status' => MerchantStatus::class,
        'ownership_type' => OwnershipType::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'merchant_user', 'merchant_id', 'user_id')
            ->using(MerchantUser::class)
            ->withTimestamps();
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function merchantStocks(): HasMany
    {
        return $this->hasMany(MerchantStock::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    public function isWarehouse(): bool
    {
        return $this->type === MerchantType::Warehouse;
    }

    public function isMerchant(): bool
    {
        return $this->type === MerchantType::Merchant;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (filled($this->avatar_path)) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function scopeWarehouses(Builder $query): Builder
    {
        return $query->where('type', MerchantType::Warehouse);
    }

    public function scopeMerchantsOnly(Builder $query): Builder
    {
        return $query->where('type', MerchantType::Merchant);
    }

    public static function warehouse(): ?self
    {
        return static::query()->where('type', MerchantType::Warehouse)->first();
    }

    protected static function booted(): void
    {
        static::created(function (self $model): void {
            if ($model->current_status && ! $model->statuses()->exists()) { // @phpstan-ignore booleanAnd.leftAlwaysTrue
                $model->spatieSetStatus($model->current_status->value, null);
            }
        });
    }

    /**
     * Get the enum class for status validation.
     */
    public function statusEnumClass(): string
    {
        return MerchantStatus::class;
    }

    /**
     * Override the setStatus method to use the hybrid strategy.
     * Keeps the local current_status column synchronized with the status history.
     */
    public function setStatus(MerchantStatus|string $name, ?string $reason = null): ?Status
    {
        $this->spatieSetStatus($name, $reason);

        // Synchronize with the local indexed column
        $this->update(['current_status' => $name]);

        // Return the newly created status record (latest status after creation)
        /** @var Status|null $status */
        $status = $this->statuses()->latest('id')->first();

        return $status;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }
}
