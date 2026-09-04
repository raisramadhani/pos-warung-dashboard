<?php

namespace App\Filament\Merchant\Resources\StockOpnames;

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\Schemas\StockOpnameForm;
use App\Filament\Merchant\Resources\StockOpnames\Schemas\StockOpnameInfolist;
use App\Filament\Merchant\Resources\StockOpnames\Tables\StockOpnamesTable;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $slug = 'stock-opname';

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'opname_number';

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof StockOpname ? $record->opname_number : null;
    }

    public static function form(Schema $schema): Schema
    {
        return StockOpnameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockOpnamesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockOpnameInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id)
            ->withCount('items');
    }

    public static function hasOpenOpname(): bool
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return false;
        }

        return StockOpname::query()
            ->where('merchant_id', $tenant->id)
            ->whereIn('status', [
                StockOpnameStatus::Draft,
                StockOpnameStatus::Counting,
                StockOpnameStatus::Reconciling,
            ])
            ->exists();
    }

    public static function isTransactionLocked(): bool
    {
        return static::getActiveLockedOpname() !== null;
    }

    public static function getActiveLockedOpname(): ?StockOpname
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return null;
        }

        return StockOpname::query()
            ->where('merchant_id', $tenant->id)
            ->where('is_lock_transactions', true)
            ->whereIn('status', [
                StockOpnameStatus::Draft,
                StockOpnameStatus::Counting,
                StockOpnameStatus::Reconciling,
            ])
            ->first();
    }
}
