<?php

namespace App\Filament\Merchant\Resources\StockMovements;

use App\Filament\Merchant\Resources\StockMovements\Schemas\StockMovementInfolist;
use App\Filament\Merchant\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $modelLabel = 'Riwayat Stok';

    protected static ?string $pluralModelLabel = 'Riwayat Stok';

    protected static ?string $navigationLabel = 'Riwayat Stok';

    protected static ?string $slug = 'stock-movements';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewStockMovement::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockMovementInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id)
            ->with(['item', 'creator']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}
