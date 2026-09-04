<?php

namespace App\Filament\Merchant\Resources\GoodsReceipts;

use App\Filament\Merchant\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\Schemas\GoodsReceiptForm;
use App\Filament\Merchant\Resources\GoodsReceipts\Schemas\GoodsReceiptInfolist;
use App\Filament\Merchant\Resources\GoodsReceipts\Tables\GoodsReceiptsTable;
use App\Models\Inventories\GoodsReceipt;
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

class GoodsReceiptResource extends Resource
{
    // Saat ini merchant tidak bisa melakukan PO sehingag tidak bisa juga menerima barang, karena saat ini gudang pusat (SPV) yang melakukan PO dan menerima barang. Sehingga fitur penerimaan barang untuk merchant dimatikan.
    // Merchant hanya menerima barang dari gudang pusat (SPV) melalui distribusi. Sehingga fitur penerimaan barang untuk merchant dimatikan.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $modelLabel = 'Penerimaan';

    protected static ?string $pluralModelLabel = 'Penerimaan';

    protected static ?string $navigationLabel = 'Penerimaan Barang';

    protected static ?string $slug = 'goods-receipts';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'receipt_number';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewGoodsReceipt::class,
            EditGoodsReceipt::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return GoodsReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceiptsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GoodsReceiptInfolist::configure($schema);
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
            'index' => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'view' => ViewGoodsReceipt::route('/{record}'),
            'edit' => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
