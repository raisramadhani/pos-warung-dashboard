<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders;

use App\Filament\Merchant\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Merchant\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Filament\Merchant\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    // Saat ini merchant belum bisa PO, karena saat ini gudang pusat (SPV) yang Purchasing, lalu mendistribusikan ke merchant.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $modelLabel = 'Purchase Order';

    protected static ?string $pluralModelLabel = 'Purchase Orders';

    protected static ?string $navigationLabel = 'Purchase Order';

    protected static ?string $slug = 'purchase-orders';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'po_number';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPurchaseOrder::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id);
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
