<?php

namespace App\Filament\Admin\Resources\Payrolls\Actions;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Payrolls\Payroll;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ApprovePayrollAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setujui';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Setujui')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Setujui Slip Gaji')
            ->modalDescription('Apakah Anda yakin ingin menyetujui slip gaji ini? Status akan berubah menjadi Disetujui.')
            ->modalSubmitActionLabel('Ya, Setujui')
            ->visible(fn (Payroll $record): bool => $record->status === PayrollStatus::Draft)
            ->action(function (Payroll $record): void {
                $record->update(['status' => PayrollStatus::Approved]);

                Notification::make()
                    ->title('Slip gaji disetujui')
                    ->success()
                    ->send();
            });
    }
}
