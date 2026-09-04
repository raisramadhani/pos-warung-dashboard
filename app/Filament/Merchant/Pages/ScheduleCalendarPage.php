<?php

namespace App\Filament\Merchant\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ScheduleCalendarPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Jadwal Shift';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-calendar-week';

    protected static ?string $navigationLabel = 'Kalender';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'schedule-calendar';

    protected string $view = 'filament.merchant.schedules.calendar';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
        return 'Jadwal shift karyawan di outlet';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Kalender';
    }
}
