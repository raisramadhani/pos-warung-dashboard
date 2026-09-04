<?php

namespace App\Models\Payrolls;

use App\Data\BonusTiersData;
use App\Enums\Payrolls\PayrollStatus;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Traits\ActivityLogs;
use Database\Factories\Payrolls\PayrollFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\DataCollection;

/**
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $user_id
 * @property Carbon $period_start Awal periode penggajian
 * @property Carbon $period_end Akhir periode penggajian
 * @property PayrollStatus $status Status payroll: draft, approved, paid, dst.
 * @property int $total_amount Total gaji dalam satuan terkecil (bisa negatif jika ada potongan)
 * @property int $bonus_target Target transaksi harian (cup)
 * @property int $bonus_base_amount Bonus dasar per orang per hari
 * @property DataCollection|null $bonus_tiers Konfigurasi kelipatan bonus: [{step, amount}, ...]
 * @property string|null $notes Catatan tambahan payroll
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read User|null $creator
 * @property-read Collection<int, PayrollItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read User|null $user
 *
 * @method static \Database\Factories\Payrolls\PayrollFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Payroll extends Model
{
    use ActivityLogs;

    /** @use HasFactory<PayrollFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'payrolls';

    protected $fillable = [
        'merchant_id',
        'user_id',
        'period_start',
        'period_end',
        'status',
        'total_amount',
        'notes',
        'bonus_target',
        'bonus_base_amount',
        'bonus_tiers',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => PayrollStatus::class,
            'total_amount' => 'integer',
            'bonus_target' => 'integer',
            'bonus_base_amount' => 'integer',
            'bonus_tiers' => DataCollection::class.':'.BonusTiersData::class,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getTotalAmountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('amount');
        }

        return (int) ($this->attributes['total_amount'] ?? 0);
    }
}
