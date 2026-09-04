<?php

namespace App\Filament\Admin\Resources\Payrolls\Actions;

use App\Models\Payrolls\Payroll;
use App\Services\PayslipExportService;
use Filament\Actions\Action;

class ExportPayslipAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportPayslip';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function (Payroll $record) {
                return app(PayslipExportService::class)->exportSingle($record);
            });
    }
}
