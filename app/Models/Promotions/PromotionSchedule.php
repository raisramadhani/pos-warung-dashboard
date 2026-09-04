<?php

namespace App\Models\Promotions;

use Database\Factories\Promotions\PromotionScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int|null $day_of_week Hari dalam seminggu (1=Senin sampai 7=Minggu). NULL = berlaku setiap hari
 * @property string|null $start_time Jam mulai jendela promo. NULL = mulai dari awal hari
 * @property string|null $end_time Jam selesai jendela promo. Jika end_time < start_time, jendela lintas tengah malam. NULL = sampai akhir hari
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Promotion|null $promotion
 *
 * @method static \Database\Factories\Promotions\PromotionScheduleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionSchedule query()
 *
 * @mixin \Eloquent
 */
class PromotionSchedule extends Model
{
    /** @use HasFactory<PromotionScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
