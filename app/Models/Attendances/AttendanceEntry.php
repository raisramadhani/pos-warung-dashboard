<?php

namespace App\Models\Attendances;

use App\Models\Merchants\Merchant;
use Database\Factories\Attendances\AttendanceEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @internal Not in active use — kept for future reactivation of attendance-based bonus automation.
 *
 * @property int $id
 * @property int $attendance_sheet_id
 * @property int $merchant_id
 * @property Carbon $date Tanggal kehadiran
 * @property int $employee_count Jumlah karyawan masuk
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Merchant|null $merchant
 * @property-read AttendanceSheet|null $sheet
 *
 * @method static \Database\Factories\Attendances\AttendanceEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEntry query()
 *
 * @mixin \Eloquent
 */
class AttendanceEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_sheet_id',
        'merchant_id',
        'date',
        'employee_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'employee_count' => 'integer',
        ];
    }

    protected static function newFactory(): AttendanceEntryFactory
    {
        return AttendanceEntryFactory::new();
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
