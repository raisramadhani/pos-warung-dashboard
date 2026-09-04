<?php

namespace App\Filament\Admin\Resources\Stocks;

use App\Filament\Admin\Resources\Stocks\Schemas\StockInfolist;
use App\Filament\Admin\Resources\Stocks\Tables\StocksTable;
use App\Models\Inventories\MerchantStock;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockResource extends Resource
{
    protected static ?string $model = MerchantStock::class;

    protected static ?string $modelLabel = 'Stok Terkini';

    protected static ?string $pluralModelLabel = 'Stok Terkini';

    protected static ?string $navigationLabel = 'Stok Terkini';

    protected static ?string $slug = 'stocks';

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 5;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewStock::class,
        ]);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof MerchantStock ? $record->item->name : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['item', 'merchant']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'view' => Pages\ViewStock::route('/{record}'),
        ];
    }
}
