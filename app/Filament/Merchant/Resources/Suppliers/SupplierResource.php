<?php

namespace App\Filament\Merchant\Resources\Suppliers;

use App\Filament\Merchant\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Merchant\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Merchant\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Merchant\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Merchant\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Merchant\Resources\Suppliers\Schemas\SupplierInfolist;
use App\Filament\Merchant\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SupplierResource extends Resource
{
    // Saat ini merchant belum bisa PO, karena saat ini gudang pusat (SPV) yang Purchasing, lalu mendistribusikan ke merchant. Sehingga Supplier juga off dlu
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Supplier::class;

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Supplier';

    protected static ?string $navigationLabel = 'Supplier';

    protected static ?string $slug = 'suppliers';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewSupplier::class,
            EditSupplier::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id)
            ->withCount('purchaseOrders');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
