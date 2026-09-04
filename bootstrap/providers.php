<?php

use App\Providers\AppServiceProvider;
use App\Providers\ComponentServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\MerchantPanelProvider;

return [
    AppServiceProvider::class,
    ComponentServiceProvider::class,
    AdminPanelProvider::class,
    MerchantPanelProvider::class,
];
