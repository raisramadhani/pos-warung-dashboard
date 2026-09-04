<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use App\Filament\Admin\Resources\Payrolls\Schemas\PayrollForm;
use App\Models\Payrolls\Payroll;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected ?string $heading = 'Edit Slip Gaji';

    protected ?string $subheading = 'Ubah data slip gaji';

    public function form(Schema $schema): Schema
    {
        \assert($this->record instanceof Payroll);

        $this->record->load('merchant');

        if (\in_array($this->record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])) {
            return $schema->disabled();
        }

        return PayrollForm::configure($schema);
    }

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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record instanceof Payroll && ! \in_array($this->record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Payroll $record */
        $record = $this->record;

        $data['period'] = $record->period_start->format('d/m/Y').' - '.$record->period_end->format('d/m/Y');
        $data['ownership_type'] = $record->merchant?->ownership_type?->value;

        $data['bonus_target'] = $record->bonus_target ?? 400;
        $data['bonus_base_amount'] = $record->bonus_base_amount ?? 5000;
        $bonusTiers = $record->bonus_tiers;
        $data['bonus_tiers'] = \count($bonusTiers) > 0 ? $bonusTiers->toArray() : [['step' => 25, 'amount' => 10000]];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \assert($this->record instanceof Payroll);

        if (\in_array($this->record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])) {
            $this->halt();
        }

        $period = $data['period'] ?? '';
        if ($period) {
            [$start, $end] = parse_period($period);
            $data['period_start'] = $start->format('Y-m-d');
            $data['period_end'] = $end->format('Y-m-d');
        }

        // Normalize bonus fields from masked money input to integers
        $data['bonus_target'] = (int) to_number($data['bonus_target'] ?? 400);
        $data['bonus_base_amount'] = (int) to_number($data['bonus_base_amount'] ?? 5000);
        $data['bonus_tiers'] = array_map(
            fn (array $t) => ['step' => (int) to_number($t['step']), 'amount' => (int) to_number($t['amount'])],
            $data['bonus_tiers'] ?? [['step' => 25, 'amount' => 10000]],
        );

        unset(
            $data['period'],
            $data['ownership_type'],
            $data['attendance_sheet_id'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        \assert($this->record instanceof Payroll);

        if (\in_array($this->record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])) {
            $this->halt();
        }

        \assert($this->record instanceof Payroll);

        $totalAmount = $this->record->items()->sum('amount');
        $this->record->update(['total_amount' => $totalAmount]);
    }
}
