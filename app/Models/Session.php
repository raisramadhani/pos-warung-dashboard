<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $payload
 * @property Carbon $last_activity
 *
 * @method static Builder<static>|Session newModelQuery()
 * @method static Builder<static>|Session newQuery()
 * @method static Builder<static>|Session query()
 *
 * @mixin \Eloquent
 */
class Session extends Model
{
    use Prunable;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    public function isCurrent(): bool
    {
        return $this->getKey() === session()->getId();
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('last_activity', '<=', now()->minus(months: 2));
    }
}
