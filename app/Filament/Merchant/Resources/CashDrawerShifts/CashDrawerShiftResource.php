<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts;

use App\Filament\Merchant\Resources\CashDrawerShifts\Pages\ListCashDrawerShifts;
use App\Filament\Merchant\Resources\CashDrawerShifts\Pages\ViewCashDrawerShift;
use App\Filament\Merchant\Resources\CashDrawerShifts\Schemas\CashDrawerShiftInfolist;
use App\Filament\Merchant\Resources\CashDrawerShifts\Tables\CashDrawerShiftsTable;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\Merchants\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashDrawerShiftResource extends Resource
{
    protected static ?string $model = CashDrawerShift::class;

    protected static ?string $modelLabel = 'Buka/Tutup Shift';

    protected static ?string $pluralModelLabel = 'Buka/Tutup Shift';

    protected static ?string $navigationLabel = 'Buka/Tutup Shift';

    protected static ?string $slug = 'cash-drawer-shifts';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-alarm';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'shift_number';

    public static function infolist(Schema $schema): Schema
    {
        return CashDrawerShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashDrawerShiftsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashDrawerShifts::route('/'),
            'view' => ViewCashDrawerShift::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->getKey())
            ->with(['openedBy', 'closedBy']);
    }
}
