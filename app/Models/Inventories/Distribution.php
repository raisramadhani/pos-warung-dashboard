<?php

namespace App\Models\Inventories;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Observers\DistributionObserver;
use App\Traits\ActivityLogs;
use Database\Factories\Inventories\DistributionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Distribusi mencatat pengiriman barang dari gudang/merchant sumber ke merchant tujuan.
 *
 * Alur:
 * 1. SPV membuat distribusi → status = Sent, stok gudang sumber berkurang (quantity_sent).
 * 2. Staff outlet memeriksa item satu per satu (verifikasi), mengisi quantity_received.
 * 3. Setelah semua item diperiksa (qty sesuai atau tidak), staff klik Finish.
 * 4. Saat status Finished, stok merchant tujuan bertambah sesuai quantity_received per item.
 *
 * @property int $id
 * @property int|null $source_merchant_id
 * @property int|null $merchant_id
 * @property DistributionStatus $status
 * @property string|null $notes
 * @property Carbon|null $sent_at
 * @property Carbon|null $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, DistributionItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read Merchant|null $sourceMerchant
 * @property-read Collection<int, DistributionItem> $verifiedItems
 * @property-read int|null $verified_items_count
 * @property-read bool|null $verified_items_exists
 *
 * @method static \Database\Factories\Inventories\DistributionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(DistributionObserver::class)]
class Distribution extends Model
{
    /** @use HasFactory<DistributionFactory> */
    use ActivityLogs, HasFactory, SoftDeletes;

    protected $fillable = [
        'source_merchant_id',
        'merchant_id',
        'status',
        'notes',
        'sent_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DistributionStatus::class,
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function sourceMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'source_merchant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DistributionItem::class);
    }

    /**
     * Item yang sudah diverifikasi (quantity_received > 0).
     */
    public function verifiedItems(): HasMany
    {
        return $this->items()->where('quantity_received', '>', 0);
    }
}
