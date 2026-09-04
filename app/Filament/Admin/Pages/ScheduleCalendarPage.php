<?php

namespace App\Filament\Admin\Pages;

use App\Enums\RoleType;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ScheduleCalendarPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Jadwal Shift';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-calendar-week';

    protected static ?string $navigationLabel = 'Kalender';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'schedule-calendar';

    protected string $view = 'filament.admin.schedules.calendar';

    public static function getNavigationGroup(): string
    {
        return 'Jadwal Shift';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Kalender Jadwal Shift';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Visualisasi jadwal shift karyawan dalam kalender';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Kalender';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === RoleType::SuperAdmin;
    }
}
