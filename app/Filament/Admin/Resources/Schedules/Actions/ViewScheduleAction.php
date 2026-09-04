<?php

namespace App\Filament\Admin\Resources\Schedules\Actions;

use App\Models\Schedules\UserSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;

class ViewScheduleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'viewSchedule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Lihat')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modal()
            ->modalHeading(fn (UserSchedule $record): string => 'Jadwal: '.$record->user->name)
            ->form([
                Grid::make(3)
                    ->schema([
                        Placeholder::make('user_name')
                            ->label('Karyawan')
                            ->content(fn (UserSchedule $record): string => $record->user->name),
                        Placeholder::make('merchant_name')
                            ->label('Outlet')
                            ->content(fn (UserSchedule $record): string => $record->merchant->name),
                        Placeholder::make('date_display')
                            ->label('Tanggal')
                            ->content(fn (UserSchedule $record): string => $record->date->translatedFormat('d F Y')),
                        Placeholder::make('start_time')
                            ->label('Jam Mulai')
                            ->content(fn (UserSchedule $record): string => substr($record->start_time, 0, 5)),
                        Placeholder::make('end_time')
                            ->label('Jam Selesai')
                            ->content(fn (UserSchedule $record): string => substr($record->end_time, 0, 5)),
                    ]),
                Placeholder::make('notes_display')
                    ->label('Catatan')
                    ->content(fn (UserSchedule $record): ?string => $record->notes),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
