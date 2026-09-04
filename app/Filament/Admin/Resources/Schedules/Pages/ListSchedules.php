<?php

namespace App\Filament\Admin\Resources\Schedules\Pages;

use App\Filament\Admin\Resources\Schedules\Schemas\ScheduleForm;
use App\Filament\Admin\Resources\Schedules\ShiftResource;
use App\Models\Schedules\UserSchedule;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSchedules extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected ?string $heading = 'Jadwal Shift';

    protected ?string $subheading = 'Daftar jadwal shift karyawan';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Jadwal')
                ->slideOver()
                ->modalHeading('Buat Jadwal Shift')
                ->form(ScheduleForm::fields())
                ->using(function (array $data): UserSchedule {
                    $schedule = UserSchedule::create($data);

                    Notification::make()
                        ->title('Jadwal shift dibuat')
                        ->success()
                        ->send();

                    return $schedule;
                }),
        ];
    }
}
