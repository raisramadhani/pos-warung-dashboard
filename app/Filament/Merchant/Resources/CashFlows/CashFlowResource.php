<?php

namespace App\Filament\Merchant\Resources\CashFlows;

use App\Filament\Merchant\Resources\CashFlows\Pages\ListCashFlows;
use App\Filament\Merchant\Resources\CashFlows\Schemas\CashFlowForm;
use App\Filament\Merchant\Resources\CashFlows\Tables\CashFlowsTable;
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

    protected static ?int $navigationSort = 2;

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
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->getKey());
    }
}
