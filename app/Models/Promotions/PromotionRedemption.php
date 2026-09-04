<?php

namespace App\Models\Promotions;

use App\Models\Customers\Customer;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Database\Factories\Promotions\PromotionRedemptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $transaction_id
 * @property int|null $transaction_item_id
 * @property int|null $customer_id
 * @property int $discount_amount Nilai diskon yang diterima pelanggan
 * @property Carbon $created_at Waktu promo dipakai
 * @property-read Customer|null $customer
 * @property-read Promotion|null $promotion
 * @property-read Transaction $transaction
 * @property-read TransactionItem|null $transactionItem
 *
 * @method static \Database\Factories\Promotions\PromotionRedemptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionRedemption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionRedemption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionRedemption query()
 *
 * @mixin \Eloquent
 */
class PromotionRedemption extends Model
{
    /** @use HasFactory<PromotionRedemptionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'promotion_id',
        'transaction_id',
        'transaction_item_id',
        'customer_id',

        /** @var int $discount_amount
         * Disengaja redundan dengan transaction_item.discount_amount.
         * Sehingga nilai yang tampil di sini harus sama dengan nilai yang tampil di transaction_item.discount_amount.
         */
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
