<?php

namespace App\Filament\Admin\Resources\Payrolls\Actions;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Payrolls\Payroll;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CancelPayrollAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'batalkan';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan Slip Gaji')
            ->modalDescription('Slip gaji akan dibatalkan dan tidak akan digunakan.')
            ->modalSubmitActionLabel('Ya, Batalkan')
            ->visible(fn (Payroll $record): bool => \in_array($record->status, [PayrollStatus::Draft, PayrollStatus::Approved]))
            ->action(function (Payroll $record): void {
                $record->update(['status' => PayrollStatus::Canceled]);

                Notification::make()
                    ->title('Slip gaji dibatalkan')
                    ->warning()
                    ->send();
            });
    }
}
