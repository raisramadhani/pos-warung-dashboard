<?php

namespace App\Models\Merchants;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $merchant_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser query()
 *
 * @mixin \Eloquent
 */
class MerchantUser extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'merchant_user';
}
