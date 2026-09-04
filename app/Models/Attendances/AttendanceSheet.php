<?php

namespace App\Models\Attendances;

use App\Enums\Attendances\PeriodType;
use Database\Factories\Attendances\AttendanceSheetFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @internal Not in active use — kept for future reactivation of attendance-based bonus automation.
 *
 * @property int $id
 * @property PeriodType $period_type Tipe periode: monthly atau weekly
 * @property Carbon $date_from Tanggal awal periode kehadiran
 * @property Carbon $date_to Tanggal akhir periode kehadiran
 * @property string|null $notes Catatan tambahan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, AttendanceEntry> $entries
 * @property-read int|null $entries_count
 * @property-read bool|null $entries_exists
 * @property-read string $period_label
 *
 * @method static \Database\Factories\Attendances\AttendanceSheetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSheet withoutTrashed()
 *
 * @mixin \Eloquent
 */
class AttendanceSheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_type',
        'date_from',
        'date_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => PeriodType::class,
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    protected static function newFactory(): AttendanceSheetFactory
    {
        return AttendanceSheetFactory::new();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceEntry::class);
    }

    /**
     * Get unique merchant IDs from entries.
     *
     * @return array<int, int>
     */
    public function getMerchantIds(): array
    {
        return $this->entries()
            ->select('merchant_id')
            ->distinct()
            ->pluck('merchant_id')
            ->toArray();
    }

    /**
     * Get period label for display.
     */
    public function getPeriodLabelAttribute(): string
    {
        $type = $this->period_type->getLabel();
        $from = $this->date_from->format('d M Y');
        $to = $this->date_to->format('d M Y');

        return "{$type}: {$from} — {$to}";
    }
}
