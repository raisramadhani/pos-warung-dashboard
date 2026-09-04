<?php

namespace App\Filament\Admin\Resources\StockOpnames;

use App\Filament\Admin\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Filament\Admin\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Admin\Resources\StockOpnames\Tables\StockOpnamesTable;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $slug = 'stock-opname';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Outlet';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'opname_number';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return StockOpnamesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockOpnames::route('/'),
            'view' => ViewStockOpname::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->withCount(['items', 'countedItems']);
    }

    /**
     * Admin panel tidak menerapkan default outlet yang terseleksi.
     */
    public static function defaultOutletId(): ?int
    {
        return null;
    }
}
