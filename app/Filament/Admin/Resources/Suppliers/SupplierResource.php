<?php

namespace App\Filament\Admin\Resources\Suppliers;

use App\Filament\Admin\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Admin\Resources\Suppliers\Schemas\SupplierInfolist;
use App\Filament\Admin\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $model = Supplier::class;

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Supplier';

    protected static ?string $navigationLabel = 'Supplier';

    protected static ?string $slug = 'suppliers';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-replace-user';

    protected static ?int $navigationSort = 3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewSupplier::class,
            Pages\EditSupplier::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('purchaseOrders')
            ->withAggregate('purchaseOrders', 'finished_at', 'max');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\PurchaseOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
