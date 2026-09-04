<?php

namespace App\Models\Payrolls;

use Database\Factories\Payrolls\PayrollItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $payroll_id
 * @property string $component_name Nama komponen gaji, mis. Gaji Pokok, Tunjangan Makan
 * @property int $daily_rate Tarif harian komponen (bisa negatif untuk potongan)
 * @property int $days Jumlah hari kerja (bisa negatif untuk potongan)
 * @property int $amount Total = daily_rate * days (bisa negatif untuk potongan)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payroll|null $payroll
 *
 * @method static \Database\Factories\Payrolls\PayrollItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem query()
 *
 * @mixin \Eloquent
 */
class PayrollItem extends Model
{
    /** @use HasFactory<PayrollItemFactory> */
    use HasFactory;

    protected $table = 'payroll_items';

    protected $fillable = [
        'payroll_id',
        'component_name',
        'daily_rate',
        'days',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'days' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
