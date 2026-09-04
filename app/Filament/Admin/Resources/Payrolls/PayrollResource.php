<?php

namespace App\Filament\Admin\Resources\Payrolls;

use App\Filament\Admin\Resources\Payrolls\Schemas\PayrollForm;
use App\Filament\Admin\Resources\Payrolls\Schemas\PayrollInfolist;
use App\Filament\Admin\Resources\Payrolls\Tables\PayrollsTable;
use App\Models\Payrolls\Payroll;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationLabel = 'Penggajian';

    protected static ?string $slug = 'payrolls';

    protected static string|\UnitEnum|null $navigationGroup = 'Penggajian';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 10;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return PayrollForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'bulk-create' => Pages\BulkCreatePayroll::route('/bulk-create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
