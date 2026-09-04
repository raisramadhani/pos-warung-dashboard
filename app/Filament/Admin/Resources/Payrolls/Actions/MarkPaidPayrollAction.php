<?php

namespace App\Filament\Admin\Resources\Payrolls\Actions;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Payrolls\Payroll;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class MarkPaidPayrollAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tandaiDibayar';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tandai Dibayar')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Tandai Sudah Dibayar')
            ->modalDescription('Apakah Anda yakin slip gaji ini sudah dibayarkan? Status akan berubah menjadi Dibayar.')
            ->modalSubmitActionLabel('Ya, sudah dibayar')
            ->visible(fn (Payroll $record): bool => $record->status === PayrollStatus::Approved)
            ->action(function (Payroll $record): void {
                $record->update(['status' => PayrollStatus::Paid]);

                Notification::make()
                    ->title('Slip gaji ditandai sudah dibayar')
                    ->success()
                    ->send();
            });
    }
}
