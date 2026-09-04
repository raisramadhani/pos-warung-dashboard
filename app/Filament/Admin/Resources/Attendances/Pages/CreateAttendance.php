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
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

/**
 * @property Schema $form
 */
class CreateAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AttendanceSheetResource::class;

    protected string $view = 'filament.admin.attendances.create';

    protected static ?string $title = 'Tambah Kehadiran';

    public ?string $periodType = 'monthly';

    public ?string $dateRange = null;

    public ?array $merchantIds = [];

    public ?string $notes = null;

    public ?array $data = [];

    /** @var string[] List of date strings (Y-m-d) */
    public array $dates = [];

    /** @var array<int, string> Merchant ID => name */
    public array $merchantNames = [];

    /**
     * Matrix data: [merchantId => [dateString => employeeCount, ...], ...].
     *
     * @var array<int, array<string, int>>
     */
    public array $matrix = [];

    public function mount(): void
    {
        $this->form->fill([
            'period_type' => 'monthly',
        ]);

        // Default: this month
        $this->periodType = 'monthly';
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $this->dateRange = $start->format('d/m/Y').' - '.$end->format('d/m/Y');
        $this->rebuildDates($start, $end);
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
                            ->default('monthly')
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
                ->label('Simpan Kehadiran')
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

        // Create attendance period
        $period = AttendanceSheet::query()->create([
            'period_type' => $data['period_type'] ?? $this->periodType,
            'date_from' => $dates[0],
            'date_to' => end($dates),
            'notes' => $data['notes'] ?? $this->notes,
        ]);

        // Create entries from matrix
        $entries = [];
        foreach ($this->matrix as $merchantId => $dateCounts) {
            foreach ($dateCounts as $dateStr => $count) {
                $entries[] = [
                    'attendance_sheet_id' => $period->id,
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
            ->title('Kehadiran berhasil disimpan')
            ->body('Tercatat '.\count($entries).' entri untuk '.\count($merchantIds).' outlet.')
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

    /**
     * Rebuild dates from Carbon instances.
     */
    private function rebuildDates(Carbon $from, Carbon $to): void
    {
        $this->dates = $this->generateDateStrings($from, $to);
    }

    /**
     * Generate array of date strings from Carbon range.
     *
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

    /**
     * Rebuild merchant names from selected IDs.
     */
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

    /**
     * Rebuild matrix preserving existing values where possible.
     */
    private function rebuildMatrix(): void
    {
        $this->rebuildMerchantNames();

        $newMatrix = [];
        foreach ($this->merchantNames as $merchantId => $name) {
            $newMatrix[$merchantId] = [];
            foreach ($this->dates as $dateStr) {
                // Preserve existing value if merchant+date existed
                $newMatrix[$merchantId][$dateStr] = $this->matrix[$merchantId][$dateStr] ?? 0;
            }
        }

        $this->matrix = $newMatrix;
    }
}
