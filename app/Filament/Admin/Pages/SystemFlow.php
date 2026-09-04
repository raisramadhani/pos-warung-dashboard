<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class SystemFlow extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-book-2';

    protected static ?string $navigationLabel = 'Panduan';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'system-flow';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('systemFlowTabs')
                ->tabs([
                    Tab::make('Admin')
                        ->schema([
                            View::make('system-flow.admin'),
                        ]),
                    Tab::make('Outlet')
                        ->schema([
                            View::make('system-flow.outlet'),
                        ]),
                ]),
        ]);
    }

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
        return 'Panduan langkah demi langkah penggunaan Abra POS (Admin & Outlet)';
    }
}
