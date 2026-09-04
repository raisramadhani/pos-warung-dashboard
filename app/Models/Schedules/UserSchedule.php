<?php

namespace App\Models\Schedules;

use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Traits\ActivityLogs;
use Database\Factories\Schedules\UserScheduleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $merchant_id
 * @property Carbon $date Tanggal shift
 * @property string $start_time Jam mulai
 * @property string $end_time Jam selesai
 * @property string|null $notes Catatan opsional
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Merchant|null $merchant
 * @property-read User|null $user
 *
 * @method static \Database\Factories\Schedules\UserScheduleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSchedule withoutTrashed()
 *
 * @mixin \Eloquent
 */
class UserSchedule extends Model
{
    /** @use HasFactory<UserScheduleFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'date',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
