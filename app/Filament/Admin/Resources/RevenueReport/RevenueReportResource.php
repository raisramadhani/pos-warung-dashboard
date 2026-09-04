<?php

namespace App\Filament\Admin\Resources\RevenueReport;

use App\Filament\Admin\Resources\RevenueReport\Tables\RevenueReportTable;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RevenueReportResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $modelLabel = 'Pendapatan';

    protected static ?string $pluralModelLabel = 'Pendapatan';

    protected static ?string $navigationLabel = 'Pendapatan';

    protected static ?string $slug = 'revenue-report';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return RevenueReportTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevenueReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $costPrice = "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(transaction_items.product_data, '$.cost_price')) AS DECIMAL(20,2)), 0)";

        return parent::getEloquentQuery()
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->select('transactions.*')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(transaction_items.product_data, '$.name')) as product_name")
            ->selectRaw('transaction_items.quantity as item_quantity')
            ->selectRaw('transaction_items.subtotal as item_subtotal')
            ->selectRaw("(transaction_items.subtotal - CAST(transaction_items.quantity * {$costPrice} AS SIGNED)) as gross_profit")
            ->with('merchant');
    }
}
