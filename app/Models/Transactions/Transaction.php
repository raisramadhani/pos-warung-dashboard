<?php

namespace App\Models\Transactions;

use App\Enums\Payments\PaymentMethod;
use App\Models\Activity;
use App\Models\Customers\Customer;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\PromotionRedemption;
use App\Observers\TransactionObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Transactions\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $customer_id
 * @property string $transaction_number
 * @property string|null $idempotency_key UUID unik per checkout untuk mencegah transaksi duplikat (idempotency)
 * @property PaymentMethod $payment_method
 * @property int $subtotal Total dari amount pada transaction_items
 * @property int $discount Total dari diskon yang diberikan pada transaction_items
 * @property int $total_amount Grandtotal transaksi = subtotal - discount
 * @property int|null $amount_received Uang yang diterima dari pelanggan (cash) atau total (qris)
 * @property int|null $change Kembalian yang diberikan = amount_received - total_amount
 * @property int $items_count Total jumlah item yang terjual
 * @property string|null $notes
 * @property Carbon $transaction_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Customer|null $customer
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, PromotionRedemption> $promotionRedemptions
 * @property-read int|null $promotion_redemptions_count
 * @property-read bool|null $promotion_redemptions_exists
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read bool|null $stock_movements_exists
 * @property-read Collection<int, TransactionItem> $transactionItems
 * @property-read int|null $transaction_items_count
 * @property-read bool|null $transaction_items_exists
 *
 * @method static \Database\Factories\Transactions\TransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use ActivityLogs, HasFactory;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'transaction_number',
        'idempotency_key',
        'payment_method',
        'subtotal',
        'discount',
        'total_amount',
        'amount_received',
        'change',
        'items_count',
        'notes',
        'transaction_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
            'amount_received' => 'integer',
            'change' => 'integer',
            'items_count' => 'integer',
            'transaction_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function promotionRedemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    /**
     * Get stock movements related to this transaction.
     * Uses polymorphic relationship via StockMovement.reference().
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * Total uang tunai dari transaksi penjualan ber-payment_method 'cash'
     * dalam rentang waktu [start, end) untuk sebuah merchant.
     *
     * Yang dijumlahkan adalah `total_amount` (uang bersih yang tertinggal di laci
     * setelah kembalian dikembalikan), bukan `amount_received`. Transaksi QRIS
     * dikecualikan karena uangnya tidak masuk ke laci fisik.
     *
     * Rentang menggunakan timestamp penuh pada kolom `transaction_at` (bukan
     * `created_at` dan bukan `whereDate`), agar presisi ke menit/detik.
     *
     * @param  Carbon  $start  Batas bawah inklusif.
     * @param  Carbon  $end  Batas atas eksklusif.
     */
    public static function sumCashBetween(int $merchantId, Carbon $start, Carbon $end): int
    {
        return (int) static::query()
            ->where('merchant_id', $merchantId)
            ->where('payment_method', PaymentMethod::Cash)
            ->where('transaction_at', '>=', $start)
            ->where('transaction_at', '<', $end)
            ->sum('total_amount');
    }
}
