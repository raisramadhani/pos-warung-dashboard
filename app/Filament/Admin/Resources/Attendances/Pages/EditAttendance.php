<?php

namespace App\Filament\Admin\Resources\Attendances\Pages;

use App\Enums\Attendances\PeriodType;
use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Attendances\AttendanceSheetResource;
use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

/**
 * @property Schema $form
 */
class EditAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AttendanceSheetResource::class;

    protected string $view = 'filament.admin.attendances.edit';

    protected static ?string $title = 'Edit Kehadiran';

    public ?string $periodType = null;

    public ?string $dateRange = null;

    /** @var int[]|null */
    public ?array $merchantIds = null;

    public ?string $notes = null;

    public ?array $data = [];

    /** @var string[] */
    public array $dates = [];

    /** @var array<int, string> */
    public array $merchantNames = [];

    /**
     * @var array<int, array<string, int>>
     */
    public array $matrix = [];

    public ?int $attendanceSheetId = null;

    public function getTitle(): string|Htmlable
    {
        return 'Edit Kehadiran';
    }

    public function mount(int|string $record): void
    {
        $this->attendanceSheetId = (int) $record;
        $period = AttendanceSheet::query()->with('entries.merchant')->findOrFail($record);
        $this->periodType = $period->period_type->value;
        $this->notes = $period->notes;
        $this->dateRange = $period->date_from->format('d/m/Y').' - '.$period->date_to->format('d/m/Y');

        // Build dates
        $this->dates = [];
        $carbonPeriod = CarbonPeriod::create($period->date_from, $period->date_to);
        foreach ($carbonPeriod as $date) {
            $this->dates[] = $date->format('Y-m-d');
        }

        // Build merchant IDs and names from existing entries
        $merchantIds = $period->entries->pluck('merchant_id')->unique()->sort()->values()->toArray();
        $this->merchantIds = $merchantIds;
        $this->merchantNames = Merchant::query()->whereIn('id', $merchantIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Build matrix from existing entries
        $this->matrix = [];
        foreach ($period->entries as $entry) {
            $mid = (string) $entry->merchant_id;
            $dateStr = $entry->date->format('Y-m-d');
            $this->matrix[$mid][$dateStr] = $entry->employee_count;
        }

        // Fill missing cells with 0
        foreach ($this->merchantNames as $merchantId => $name) {
            foreach ($this->dates as $dateStr) {
                if (! isset($this->matrix[$merchantId][$dateStr])) {
                    $this->matrix[$merchantId][$dateStr] = 0;
                }
            }
        }

        $this->form->fill([
            'period_type' => $this->periodType,
            'date_range' => $this->dateRange,
            'merchant_ids' => $this->merchantIds,
            'notes' => $this->notes,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Kehadiran')
                    ->description('Pilih periode dan outlet untuk pencatatan kehadiran')
                    ->schema([
                        Select::make('period_type')
                            ->label('Tipe Periode')
                            ->options(PeriodType::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, PeriodType|string|null $state): void {
                                $value = $state instanceof PeriodType ? $state->value : $state;
                                $this->periodType = $value;
                                if ($value === 'monthly') {
                                    $start = Carbon::now()->startOfMonth();
                                    $end = Carbon::now()->endOfMonth();
                                } else {
                                    $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
                                    $end = $start->copy()->addDays(6);
                                }
                                $range = $start->format('d/m/Y').' - '.$end->format('d/m/Y');
                                $set('date_range', $range);
                                $this->dateRange = $range;
                                $this->rebuildDates($start, $end);
                                $this->rebuildMatrix();
                            }),
                        DateRangePicker::make('date_range')
                            ->label('Rentang Tanggal')
                            ->placeholder('Pilih rentang tanggal')
                            ->required()
                            ->autoApply()
                            ->live()
                            ->afterStateUpdated(function (?string $state): void {
                                $this->dateRange = $state;
                                $this->parseAndRebuild();
                            }),
                        Select::make('merchant_ids')
                            ->label('Outlet')
                            ->options(
                                Merchant::query()->where('type', MerchantType::Merchant)
                                    ->where('current_status', MerchantStatus::Active)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih outlet')
                            ->live()
                            ->afterStateUpdated(function (?array $state): void {
                                $this->merchantIds = $state ?? [];
                                $this->rebuildMerchantNames();
                                $this->rebuildMatrix();
                            }),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->nullable()
                            ->maxLength(1000),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Simpan Perubahan')
                ->submit('submit'),
        ];
    }

    public function submit(): void
    {
        $this->form->validate();

        $data = $this->form->getState();

        $dateRange = $data['date_range'] ?? $this->dateRange;
        $merchantIds = $data['merchant_ids'] ?? $this->merchantIds;

        if (empty($dateRange) || empty($merchantIds)) {
            Notification::make()
                ->title('Lengkapi data')
                ->body('Pilih periode dan minimal satu outlet.')
                ->warning()
                ->send();

            return;
        }

        $dates = $this->parseDates($dateRange);
        if (empty($dates)) {
            Notification::make()
                ->title('Rentang tanggal tidak valid')
                ->warning()
                ->send();

            return;
        }

        // Update period
        $attendanceSheet = AttendanceSheet::query()->findOrFail($this->attendanceSheetId);

        $attendanceSheet->update([
            'period_type' => $data['period_type'] ?? $this->periodType,
            'date_from' => $dates[0],
            'date_to' => end($dates),
            'notes' => $data['notes'] ?? $this->notes,
        ]);

        // Delete old entries, insert new ones
        $attendanceSheet->entries()->delete();

        $entries = [];
        foreach ($this->matrix as $merchantId => $dateCounts) {
            foreach ($dateCounts as $dateStr => $count) {
                $entries[] = [
                    'attendance_sheet_id' => $attendanceSheet->id,
                    'merchant_id' => (int) $merchantId,
                    'date' => $dateStr,
                    'employee_count' => (int) $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($entries)) {
            AttendanceEntry::insert($entries);
        }

        Notification::make()
            ->title('Kehadiran berhasil diperbarui')
            ->success()
            ->send();

        $this->redirect(AttendanceSheetResource::getUrl('index'));
    }

    /**
     * Parse date range string and rebuild dates array.
     */
    private function parseAndRebuild(): void
    {
        $dates = $this->parseDates($this->dateRange);
        if (! empty($dates)) {
            $this->dates = $dates;
            $this->rebuildMatrix();
        }
    }

    /**
     * Parse date range string (d/m/Y - d/m/Y) into Carbon dates.
     *
     * @return string[]
     */
    private function parseDates(?string $range): array
    {
        if (empty($range)) {
            return [];
        }

        $parts = explode(' - ', $range);
        if (\count($parts) !== 2) {
            return [];
        }

        try {
            $from = Carbon::createFromFormat('d/m/Y', trim($parts[0]));
            $to = Carbon::createFromFormat('d/m/Y', trim($parts[1]));
        } catch (\Exception) {
            return [];
        }

        return $this->generateDateStrings($from, $to);
    }

    private function rebuildDates(Carbon $from, Carbon $to): void
    {
        $this->dates = $this->generateDateStrings($from, $to);
    }

    /**
     * @return string[]
     */
    private function generateDateStrings(Carbon $from, Carbon $to): array
    {
        $dates = [];
        $period = CarbonPeriod::create($from, $to);
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    private function rebuildMerchantNames(): void
    {
        if (empty($this->merchantIds)) {
            $this->merchantNames = [];

            return;
        }

        $this->merchantNames = Merchant::query()->whereIn('id', $this->merchantIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private function rebuildMatrix(): void
    {
        $this->rebuildMerchantNames();

        $newMatrix = [];
        foreach ($this->merchantNames as $merchantId => $name) {
            $newMatrix[$merchantId] = [];
            foreach ($this->dates as $dateStr) {
                $newMatrix[$merchantId][$dateStr] = $this->matrix[$merchantId][$dateStr] ?? 0;
            }
        }

        $this->matrix = $newMatrix;
    }
}
