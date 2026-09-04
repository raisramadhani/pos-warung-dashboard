<?php

namespace App\Models\Inventories;

use App\Models\Merchants\Merchant;
use Database\Factories\Inventories\MerchantStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Saldo stok saat ini per merchant per item.
 *
 * Berbeda dengan StockMovement yang mencatat riwayat mutasi,
 * model ini menyimpan jumlah stok terkini (running balance).
 * Ditulis oleh StockMovementService::increase() / decrease(),
 * dibaca untuk validasi stok dan tampilan daftar stok.
 *
 * @property int $id
 * @property int $merchant_id
 * @property int $item_id
 * @property float $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Item|null $item
 * @property-read Merchant|null $merchant
 *
 * @method static \Database\Factories\Inventories\MerchantStockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock query()
 *
 * @mixin \Eloquent
 */
class MerchantStock extends Model
{
    /** @use HasFactory<MerchantStockFactory> */
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
