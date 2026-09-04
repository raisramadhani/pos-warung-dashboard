<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use App\Models\Payrolls\Payroll;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected ?string $heading = 'Buat Slip Gaji';

    /**
     * Whether the current save is a draft save.
     */
    protected bool $savingAsDraft = false;

    // protected ?string $subheading = 'Buat slip gaji baru untuk karyawan';

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

    protected function getFormActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Sebagai Draft')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->action(fn () => $this->createDraft()),
            $this->getCreateFormAction()
                ->label('Simpan & Setujui'),
        ];
    }

    /**
     * Save the payroll as a draft.
     */
    public function createDraft(): void
    {
        $this->savingAsDraft = true;

        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Parse DateRangePicker period into period_start/period_end
        $period = $data['period'] ?? '';
        if ($period) {
            [$start, $end] = parse_period($period);
            $data['period_start'] = $start->format('Y-m-d');
            $data['period_end'] = $end->format('Y-m-d');
        }

        // Remove form-only fields
        unset(
            $data['period'],
            $data['ownership_type'],
            $data['attendance_sheet_id'],
        );

        // Normalize bonus fields from masked money input to integers
        $data['bonus_target'] = (int) to_number($data['bonus_target'] ?? 400);
        $data['bonus_base_amount'] = (int) to_number($data['bonus_base_amount'] ?? 5000);
        $data['bonus_tiers'] = array_map(
            fn (array $t) => ['step' => (int) to_number($t['step']), 'amount' => (int) to_number($t['amount'])],
            $data['bonus_tiers'] ?? [['step' => 25, 'amount' => 10000]],
        );

        $data['status'] = $this->savingAsDraft
            ? PayrollStatus::Draft->value
            : PayrollStatus::Approved->value;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Payroll $record */
        $record = $this->record;

        $record->load('user');
        $totalAmount = $record->items()->sum('amount');
        $record->update(['total_amount' => $totalAmount]);

        $isDraft = $record->status === PayrollStatus::Draft;

        Notification::make()
            ->title($isDraft ? 'Slip gaji disimpan sebagai draft' : 'Slip gaji dibuat')
            ->body("Slip gaji untuk {$record->user?->name} periode {$record->period_start->format('d F Y')} - {$record->period_end->format('d F Y')} telah dibuat.")
            ->{$isDraft ? 'warning' : 'success'}()
            ->send();
    }
}
