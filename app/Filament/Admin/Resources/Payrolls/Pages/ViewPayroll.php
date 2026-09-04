<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Actions\ApprovePayrollAction;
use App\Filament\Admin\Resources\Payrolls\Actions\CancelPayrollAction;
use App\Filament\Admin\Resources\Payrolls\Actions\ExportPayslipAction;
use App\Filament\Admin\Resources\Payrolls\Actions\MarkPaidPayrollAction;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use App\Filament\Admin\Resources\Payrolls\RelationManagers\ItemsRelationManager;
use App\Models\Payrolls\Payroll;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected ?string $heading = 'Slip Gaji';

    protected string $view = 'filament.admin.payrolls.payslip';

    protected ?string $subheading = 'Detail slip gaji karyawan';

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
        $record = $this->getRecord();

        \assert($record instanceof Payroll);

        return [
            Actions\EditAction::make()
                ->visible(! \in_array($record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])),
            ApprovePayrollAction::make()
                ->after(function (Payroll $record): void {
                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),
            CancelPayrollAction::make()
                ->after(function (Payroll $record): void {
                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),
            MarkPaidPayrollAction::make()
                ->after(function (Payroll $record): void {
                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()']),
            ExportPayslipAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public function getViewData(): array
    {
        return [
            'payroll' => $this->getRecord()->load('items', 'user', 'merchant'),
        ];
    }
}
