<?php

namespace App\Filament\Admin\Resources\Attendances;

use App\Filament\Admin\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendances\AttendanceSheet;
use Filament\Resources\Resource;
// use Filament\Support\Icons\Heroicon; // TODO: re-enable when attendance feature is active
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @internal Not in active use — kept for future reactivation of attendance-based bonus automation.
 */
class AttendanceSheetResource extends Resource
{
    protected static ?string $model = AttendanceSheet::class;

    // TODO: re-enable when attendance feature is active
    // protected static ?string $navigationLabel = 'Kehadiran';
    // protected static ?string $slug = 'attendances';
    // protected static string|\UnitEnum|null $navigationGroup = 'Penggajian';
    // protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    // protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // TODO: re-enable when attendance feature is active
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['entries.merchant']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
