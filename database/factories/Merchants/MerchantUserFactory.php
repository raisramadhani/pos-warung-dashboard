<?php

namespace Database\Factories\Merchants;

use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantUser>
 */
class MerchantUserFactory extends Factory
{
    protected $model = MerchantUser::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'user_id' => User::factory(),
        ];
    }
}
