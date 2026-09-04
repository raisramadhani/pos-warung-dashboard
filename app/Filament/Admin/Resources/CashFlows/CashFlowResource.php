<?php

namespace App\Filament\Admin\Resources\CashFlows;

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\CashFlows\Pages\ListCashFlows;
use App\Filament\Admin\Resources\CashFlows\Schemas\CashFlowForm;
use App\Filament\Admin\Resources\CashFlows\Tables\CashFlowsTable;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;

    protected static ?string $modelLabel = 'Keuangan';

    protected static ?string $pluralModelLabel = 'Keuangan';

    protected static ?string $navigationLabel = 'Keuangan';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(CashFlowForm::fields());
    }

    public static function table(Table $table): Table
    {
        return CashFlowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashFlows::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn(
                'merchant_id',
                Merchant::query()
                    ->whereIn('type', [MerchantType::Warehouse, MerchantType::Merchant])
                    ->select('id')
            );
    }
}
