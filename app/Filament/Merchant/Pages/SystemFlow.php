<?php

namespace App\Filament\Merchant\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SystemFlow extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-book-2';

    protected static ?string $navigationLabel = 'Panduan';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'system-flow';

    protected string $view = 'filament.merchant.system-flow';

    public function getTitle(): string|Htmlable
    {
        return 'Panduan Penggunaan Sistem';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Panduan Penggunaan Sistem';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Panduan langkah demi langkah penggunaan Abra POS untuk Outlet';
    }
}
