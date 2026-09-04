<?php

namespace App\Filament\Admin\Resources\Payrolls\BulkActions;

use App\Services\PayslipExportService;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ExportPayslipBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'exportPayslipBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Excel Slip Gaji')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function (Collection $records) {
                return app(PayslipExportService::class)->exportBulk(collect($records));
            });
    }
}
