<?php

namespace App\Observers;

use App\Models\Merchants\Merchant;
use Illuminate\Support\Facades\Cache;

class MerchantObserver
{
    public function created(Merchant $merchant): void
    {
        $this->forgetSuperAdminMerchantCache();
    }

    public function deleted(Merchant $merchant): void
    {
        $this->forgetSuperAdminMerchantCache();
    }

    protected function forgetSuperAdminMerchantCache(): void
    {
        Cache::forget('super_admin:merchants');
        Cache::forget('super_admin:default_merchant');
    }
}
