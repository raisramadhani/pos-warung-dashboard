<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Services\AssetDepreciationService;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\AssetFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $item_id
 * @property int|null $merchant_id
 * @property string $name
 * @property string|null $description
 * @property Carbon $acquisition_date
 * @property numeric $acquisition_cost
 * @property int|null $useful_life_months
 * @property numeric $salvage_value
 * @property DepreciationMethod $depreciation_method
 * @property AssetStatus $status
 * @property Carbon|null $last_depreciation_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, AssetDepreciation> $depreciations
 * @property-read int|null $depreciations_count
 * @property-read bool|null $depreciations_exists
 * @property-read float $accumulated_depreciation
 * @property-read float $annual_depreciation_rate
 * @property-read float $current_book_value
 * @property-read float $monthly_depreciation
 * @property-read Item|null $item
 * @property-read Merchant|null $merchant
 *
 * @method static \Database\Factories\Inventories\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'merchant_id',
        'name',
        'description',
        'acquisition_date',
        'acquisition_cost',
        'useful_life_months',
        'salvage_value',
        'depreciation_method',
        'status',
        'last_depreciation_date',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'depreciation_method' => DepreciationMethod::class,
            'status' => AssetStatus::class,
            'last_depreciation_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    /**
     * Beban penyusutan untuk periode berjalan.
     * Menghitung ulang langsung via service (Straight Line / Reduce Balance), bukan dari database.
     */
    public function getMonthlyDepreciationAttribute(): float
    {
        return app(AssetDepreciationService::class)->monthlyAmountForPeriod($this, now()->startOfMonth());
    }

    /**
     * Persentase penyusutan tahunan (Nilai / Tahun).
     * Straight Line = 100% / umur tahun; Reduce Balance (double declining) = 200% / umur tahun.
     */
    public function getAnnualDepreciationRateAttribute(): float
    {
        return app(AssetDepreciationService::class)->annualRatePercent($this);
    }

    /**
     * Total seluruh penyusutan yang telah dicatat (sum dari tabel asset_depreciations).
     */
    public function getAccumulatedDepreciationAttribute(): float
    {
        return (float) $this->depreciations()->sum('depreciation_amount');
    }

    /**
     * Nilai buku saat ini = harga akuisisi - akumulasi penyusutan.
     */
    public function getCurrentBookValueAttribute(): float
    {
        return $this->acquisition_cost - $this->accumulated_depreciation;
    }
}
