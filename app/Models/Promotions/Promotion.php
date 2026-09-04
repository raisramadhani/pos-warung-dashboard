<?php

namespace App\Models\Promotions;

use App\Enums\Promotions\PromotionType;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\TransactionItem;
use App\Traits\ActivityLogs;
use Database\Factories\Promotions\PromotionFactory;
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
 * @property string $name Nama promo, mis. Jumat Berkah, Buy 2 Get 1
 * @property string $slug Slug unik promo, dibuat otomatis dari nama
 * @property string|null $description Deskripsi atau keterangan tambahan promo
 * @property PromotionType $type Tipe promo: bundle_fixed_price, buy_x_get_y, flash_sale_price, percent_discount, fixed_discount
 * @property bool $is_active Status aktif promo. Nonaktif tidak pernah dievaluasi
 * @property Carbon|null $starts_at Tanggal mulai berlaku promo. NULL = berlaku sejak kapan saja
 * @property Carbon|null $ends_at Tanggal berakhir promo. NULL = berlaku selamanya
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, PromotionCondition> $conditions
 * @property-read int|null $conditions_count
 * @property-read bool|null $conditions_exists
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, PromotionRedemption> $redemptions
 * @property-read int|null $redemptions_count
 * @property-read bool|null $redemptions_exists
 * @property-read Collection<int, PromotionReward> $rewards
 * @property-read int|null $rewards_count
 * @property-read bool|null $rewards_exists
 * @property-read Collection<int, PromotionSchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read bool|null $schedules_exists
 * @property-read Collection<int, TransactionItem> $transactionItems
 * @property-read int|null $transaction_items_count
 * @property-read bool|null $transaction_items_exists
 *
 * @method static \Database\Factories\Promotions\PromotionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use ActivityLogs, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PromotionSchedule::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PromotionCondition::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(PromotionReward::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Snapshot promo untuk disimpan di transaction_items.promotion_data.
     * Menangkap nama, tipe, syarat, dan hadiah agar tetap tercatat walau data
     * promo diubah/dihapus. Butuh relasi conditions.product/category & rewards.product
     * sudah di-eager-load (lihat PromotionService::getActivePromotionsForMerchant).
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     conditions: array<int, array{product_name: string|null, category_name: string|null, min_quantity: int}>,
     *     rewards: array<int, array{reward_type: string, product_name: string|null, quantity: int|null, value: int|null}>,
     * }
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'conditions' => $this->conditions->map(fn (PromotionCondition $condition): array => [
                'product_name' => $condition->product?->name,
                'category_name' => $condition->category?->name,
                'min_quantity' => $condition->min_quantity,
            ])->values()->all(),
            'rewards' => $this->rewards->map(fn (PromotionReward $reward): array => [
                'reward_type' => $reward->reward_type->value,
                'product_name' => $reward->product?->name,
                'quantity' => $reward->quantity,
                'value' => $reward->value,
            ])->values()->all(),
        ];
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
