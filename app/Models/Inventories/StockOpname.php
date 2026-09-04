<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Observers\StockOpnameObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Stock opname mencatat sesi penghitungan stok fisik oleh staff merchant.
 *
 * Alur:
 * 1. Staff membuat sesi -> status Draft, pilih item yang akan dihitung.
 * 2. Staff mulai penghitungan -> status Counting, stok sistem di-freeze (snapshot).
 * 3. Staff input stok fisik untuk setiap item -> status Counting.
 * 4. Staff review selisih -> status Reconciling.
 * 5. Staff selesaikan -> status Completed, stok disesuaikan via StockMovementService::adjust().
 *
 * Opsional: is_lock_transactions = true mencegah transaksi keluar selama Counting/Reconciling.
 *
 * @property int $id
 * @property int $merchant_id
 * @property string $opname_number
 * @property StockOpnameStatus $status
 * @property bool $is_lock_transactions
 * @property int $total_items
 * @property int $total_surplus
 * @property int $total_deficit
 * @property int $total_difference
 * @property string|null $notes
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $canceled_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, StockOpnameItem> $countedItems
 * @property-read int|null $counted_items_count
 * @property-read bool|null $counted_items_exists
 * @property-read User|null $creator
 * @property-read Collection<int, StockOpnameItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 *
 * @method static \Database\Factories\Inventories\StockOpnameFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(StockOpnameObserver::class)]
class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'opname_number',
        'status',
        'is_lock_transactions',
        'total_items',
        'total_surplus',
        'total_deficit',
        'total_difference',
        'notes',
        'started_at',
        'completed_at',
        'canceled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockOpnameStatus::class,
            'is_lock_transactions' => 'boolean',
            'total_items' => 'integer',
            'total_surplus' => 'integer',
            'total_deficit' => 'integer',
            'total_difference' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Merchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<StockOpnameItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /**
     * Item yang sudah dihitung (actual_quantity terisi).
     */
    public function countedItems(): HasMany
    {
        return $this->items()->whereNotNull('actual_quantity');
    }
}
