<?php

namespace App\Models\CashDrawer;

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Observers\CashDrawerShiftObserver;
use App\Traits\ActivityLogs;
use Database\Factories\CashDrawer\CashDrawerShiftFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $merchant_id
 * @property string $shift_number
 * @property CashDrawerShiftStatus $status
 * @property int $opening_amount
 * @property string|null $opening_note
 * @property int|null $expected_cash_amount
 * @property int|null $declared_cash_amount
 * @property int|null $difference
 * @property int|null $opened_by
 * @property int|null $closed_by
 * @property Carbon|null $opened_at
 * @property Carbon|null $closed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read User|null $closedBy
 * @property-read Merchant|null $merchant
 * @property-read User|null $openedBy
 *
 * @method static \Database\Factories\CashDrawer\CashDrawerShiftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashDrawerShift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashDrawerShift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashDrawerShift query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(CashDrawerShiftObserver::class)]
class CashDrawerShift extends Model
{
    /** @use HasFactory<CashDrawerShiftFactory> */
    use ActivityLogs, HasFactory;

    protected $fillable = [
        'merchant_id',
        'shift_number',
        'status',
        'opening_amount',
        // Catatan pembukaan shift kasir, misalnya "Shift dibuka dengan baik"
        'opening_note',
        'expected_cash_amount',
        'declared_cash_amount',
        'difference',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        // Catatan penutupan shift kasir, misalnya "Shift ditutup dengan baik"
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashDrawerShiftStatus::class,
            'opening_amount' => 'integer',
            'expected_cash_amount' => 'integer',
            'declared_cash_amount' => 'integer',
            'difference' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
