<?php

namespace App\Filament\Admin\Resources\Schedules\Actions;

use App\Filament\Admin\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\Schedules\UserSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditScheduleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'editSchedule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->slideOver()
            ->modalHeading('Edit Jadwal Shift')
            ->form(ScheduleForm::fields())
            ->fillForm(fn (UserSchedule $record): array => [
                'user_id' => $record->user_id,
                'merchant_id' => $record->merchant_id,
                'date' => $record->date,
                'start_time' => substr($record->start_time, 0, 5),
                'end_time' => substr($record->end_time, 0, 5),
                'notes' => $record->notes,
            ])
            ->action(function (UserSchedule $record, array $data): void {
                $record->update($data);

                Notification::make()
                    ->title('Jadwal shift diperbarui')
                    ->success()
                    ->send();
            });
    }
}
