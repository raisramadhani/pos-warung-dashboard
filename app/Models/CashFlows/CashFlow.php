<?php

namespace App\Models\CashFlows;

use App\Enums\CashFlows\CashFlowType;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Traits\ActivityLogs;
use Database\Factories\CashFlows\CashFlowFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Arus kas masuk/keluar per merchant (pemasukan & pengeluaran).
 *
 * amount bertanda (signed): pengeluaran disimpan negatif, pemasukan disimpan positif.
 * Kolom type hanya menandai jenis; tanda pada amount yang menentukan arah perhitungan.
 *
 * @property int $id
 * @property int|null $merchant_id Merchant pemilik arus kas
 * @property CashFlowType $type Jenis arus kas: income (pemasukan) atau expense (pengeluaran)
 * @property string|null $description Keterangan opsional
 * @property int $amount Nominal bertanda: minus untuk pengeluaran, plus untuk pemasukan
 * @property bool $affects_cash_drawer Apakah nominal ini menambah/mengurangi saldo cashdrawer saat tutup shift
 * @property Carbon $transaction_date Tanggal transaksi keuangan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Merchant|null $merchant
 *
 * @method static \Database\Factories\CashFlows\CashFlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashFlow query()
 *
 * @mixin \Eloquent
 */
class CashFlow extends Model
{
    use ActivityLogs;

    /** @use HasFactory<CashFlowFactory> */
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'type',
        'description',
        // amount bertanda: pengeluaran disimpan negatif, pemasukan disimpan positif
        'amount',
        'affects_cash_drawer',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashFlowType::class,
            'amount' => 'integer',
            'affects_cash_drawer' => 'boolean',
            'transaction_date' => 'date',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Kembalikan nominal bertanda sesuai jenis: pemasukan positif, pengeluaran negatif.
     */
    public static function withSign(int $amount, CashFlowType $type): int
    {
        return $type === CashFlowType::Income ? abs($amount) : -abs($amount);
    }
}
