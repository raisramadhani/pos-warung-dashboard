<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\StockMovementType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $merchant_id
 * @property int $item_id
 * @property float $quantity
 * @property float $quantity_before
 * @property float $quantity_after
 * @property StockMovementType $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read User|null $creator
 * @property-read Item|null $item
 * @property-read Merchant|null $merchant
 * @property-read Model|\Eloquent|null $reference
 *
 * @method static \Database\Factories\Inventories\StockMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 *
 * @mixin \Eloquent
 */
class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'item_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'type',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_at', // Next seharusnya untuk tanggal pergerakan diambil dari action_at atau transaction_at, bukan created_at dari model ini. Namun, untuk saat ini tak apa gunakan created_at karena pada fitur transaksi juga belum ada Input tanggal transaksi custom. Eh pada PO/Good Reciept/Asset sudah ada tanggal custom, tapi belum diimplementasikan di sini. Berarti baiknya segera diganti. Deferred Bug Ditangguhkan.
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'float',
            'quantity_before' => 'float',
            'quantity_after' => 'float',
            'created_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
