<?php

namespace App\Filament\Admin\Resources\Schedules;

use App\Filament\Admin\Resources\Schedules\Tables\SchedulesTable;
use App\Models\Schedules\UserSchedule;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = UserSchedule::class;

    protected static ?string $navigationLabel = 'Kelola Jadwal';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $label = 'Kelola Jadwal';

    protected static ?string $pluralLabel = 'Kelola Jadwal';

    protected static ?string $slug = 'schedules';

    protected static string|\UnitEnum|null $navigationGroup = 'Jadwal Shift';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-calendar-cog';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return SchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
        ];
    }
}
