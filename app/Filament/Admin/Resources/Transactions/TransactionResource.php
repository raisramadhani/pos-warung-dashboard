<?php

namespace App\Filament\Admin\Resources\Transactions;

use App\Filament\Admin\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Admin\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Admin\Resources\Transactions\Tables\TransactionsTable;
use App\Filament\Merchant\Resources\Transactions\RelationManagers\StockMovementsRelationManager;
use App\Filament\Merchant\Resources\Transactions\RelationManagers\TransactionItemsRelationManager;
use App\Filament\Merchant\Resources\Transactions\Schemas\TransactionInfolist;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewTransaction::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionItemsRelationManager::class,
            StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->with(['merchant', 'customer', 'transactionItems', 'promotionRedemptions.transactionItem']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->with(['merchant', 'customer', 'transactionItems', 'promotionRedemptions.transactionItem']);
    }

    /**
     * Admin panel tidak menerapkan default outlet yang terseleksi.
     */
    public static function defaultOutletId(): ?int
    {
        return null;
    }
}
