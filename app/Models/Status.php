<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property string|null $reason
 * @property string $model_type
 * @property int $model_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $model
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status query()
 *
 * @mixin \Eloquent
 */
class Status extends \Spatie\ModelStatus\Status
{
    protected static function booted(): void
    {
        static::creating(function (Status $status) {
            $user = Auth::user();
            if ($user) {
                $status->actor_type = $user->getMorphClass();
                $status->actor_id = $user->getkey();
            }
        });
    }
}
