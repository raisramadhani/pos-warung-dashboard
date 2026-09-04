<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
// use App\Models\Attendances\AttendanceSheet; // TODO: re-enable when attendance feature is active
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use App\Services\PayrollBonusService;
use App\Services\PayrollDuplicateService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

/**
 * @property Schema $form
 */
class BulkCreatePayroll extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PayrollResource::class;

    protected string $view = 'filament.admin.payrolls.bulk-create';

    protected static ?string $title = 'Buat Slip Gaji Karyawan Massal';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Slip Gaji')
                    ->description('Pilih tipe kepemilikan, outlet, dan periode penggajian')
                    ->schema([
                        Grid::make(10)
                            ->columnSpanFull()
                            ->schema([
                                ToggleButtons::make('ownership_type')
                                    ->label('Tipe Kepemilikan')
                                    ->options(OwnershipType::class)
                                    ->default(OwnershipType::Main->value)
                                    ->grouped()
                                    ->required()
                                    ->inline()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('merchant_ids', []);
                                        $set('employees', []);
                                    })
                                    ->columnSpan(2),
                                DateRangePicker::make('period')
                                    ->label('Periode')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->placeholder('Pilih rentang periode')
                                    ->required()
                                    ->autoApply()
                                    ->live()
                                    ->columnSpan(4),
                                Select::make('merchant_ids')
                                    ->label('Outlet')
                                    ->prefixIcon('heroicon-o-building-storefront')
                                    ->options(function (Get $get) {
                                        $ownershipType = $get('ownership_type');

                                        return Merchant::query()
                                            ->where('current_status', MerchantStatus::Active)
                                            ->where('type', MerchantType::Merchant)
                                            ->when($ownershipType, fn ($query) => $query->where('ownership_type', $ownershipType))
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Pilih outlet')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('employees', []))
                                    ->columnSpan(4),
                            ]),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan tambahan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Pengaturan Bonus Transaksi')
                    ->description('Konfigurasi target dan bonus transaksi')
                    ->schema([
                        TextInput::make('bonus_target')
                            ->label('Target Transaksi')
                            ->required()
                            ->default(400)
                            ->minValue(0)
                            ->live()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->suffix('cup'),
                        TextInput::make('bonus_base_amount')
                            ->label('Bonus Dasar')
                            ->helperText('Nominal bonus yang didapatkan jika target terpenuhi')
                            ->required()
                            ->default(5000)
                            ->prefix('Rp')
                            ->minValue(0)
                            ->live()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state)),
                        // TODO: re-enable when attendance feature is active
                        // Select::make('attendance_sheet_id')
                        //     ->label('Daftar Kehadiran (untuk Cabang)')
                        //     ->options(AttendanceSheet::orderByDesc('date_from')->get()->mapWithKeys(fn ($sheet) => [$sheet->id => $sheet->period_label]))
                        //     ->nullable()
                        //     ->searchable()
                        //     ->preload()
                        //     ->columnSpanFull(),
                        Repeater::make('bonus_tiers')
                            ->label('Kelipatan Bonus')
                            ->addActionLabel('Tambah Kelipatan')
                            ->collapsible()
                            ->columnSpanFull()
                            ->compact()
                            ->table([
                                TableColumn::make('Jumlah Terjual'),
                                TableColumn::make('Mendapatkan Bonus Sebesar'),
                            ])
                            ->schema([
                                TextInput::make('step')
                                    ->label('Per Cup')
                                    ->required()
                                    ->default(25)
                                    ->minValue(1)
                                    ->live()
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->suffix('cup'),
                                TextInput::make('amount')
                                    ->label('Bonus')
                                    ->required()
                                    ->default(10000)
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->live()
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state)),
                            ])
                            ->default([['step' => 25, 'amount' => 10000]]),
                    ])
                    ->columns(2),
                Section::make('Rekomendasi Bonus Transaksi')
                    ->description('Data transaksi harian dan rekomendasi nominal bonus per orang per outlet. Masukkan manual di komponen gaji karyawan.')
                    ->schema([
                        TextEntry::make('bonus_recommendation')
                            ->label('')
                            ->columnSpanFull()
                            ->state(function (Get $get): HtmlString {
                                return self::renderBonusRecommendation($get);
                            }),
                    ]),
                Section::make('Komponen Gaji Master')
                    ->description('Komponen gaji standar yang otomatis diterapkan ke setiap karyawan. Isi hanya nama komponen dan nominal.')
                    ->schema([
                        Repeater::make('master_components')
                            ->label('Komponen Master')
                            ->addActionLabel('Tambah Komponen Master')
                            ->collapsible()
                            ->table([
                                TableColumn::make('Komponen'),
                                TableColumn::make('Nominal'),
                            ])
                            ->compact()
                            ->schema([
                                TextInput::make('component_name')
                                    ->label('Komponen')
                                    ->placeholder('contoh: Weekday/Weekend/Bonus')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('daily_rate')
                                    ->label('Nominal')
                                    ->placeholder('Masukkan nominal')
                                    ->required()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state)),
                            ]),
                    ]),
                Section::make('Daftar Karyawan')
                    ->description('Isi jumlah untuk setiap komponen master, atau tambahkan komponen khusus.')
                    ->schema([
                        Repeater::make('employees')
                            ->label('Karyawan')
                            ->required()
                            ->minItems(1)
                            ->collapsible()
                            ->addActionLabel('Tambah Karyawan')
                            ->itemLabel(fn (array $state): ?string => $state['user_id']
                                ? User::query()->find($state['user_id'])?->name
                                : null)
                            ->compact()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('merchant_id')
                                            ->label('Outlet')
                                            ->prefixIcon('heroicon-o-building-storefront')
                                            ->options(function (Get $get) {
                                                $merchantIds = $get('../../merchant_ids');

                                                if (empty($merchantIds)) {
                                                    return [];
                                                }

                                                return Merchant::query()->whereIn('id', $merchantIds)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->placeholder('Pilih outlet')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),
                                        Select::make('user_id')
                                            ->label('Karyawan')
                                            ->prefixIcon('heroicon-o-users')
                                            ->options(function (Get $get) {
                                                $merchantId = $get('merchant_id');

                                                if (! $merchantId) {
                                                    return [];
                                                }

                                                return User::whereHas('merchants', fn ($query) => $query->where('merchant_id', $merchantId))
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->placeholder('Pilih karyawan')
                                            ->live()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                                if (! $state) {
                                                    return;
                                                }

                                                $masterComponents = $get('../../master_components');

                                                if (! empty($masterComponents)) {
                                                    $items = [];
                                                    foreach ($masterComponents as $master) {
                                                        $name = $master['component_name'] ?? '';
                                                        if ($name === '' || $name === '0') {
                                                            continue;
                                                        }
                                                        $rate = to_number($master['daily_rate']);
                                                        $items[] = [
                                                            'component_name' => $name,
                                                            'daily_rate' => $rate,
                                                            'days' => 0,
                                                            'amount' => 0,
                                                        ];
                                                    }

                                                    $set('items', $items);
                                                }

                                                // TODO: re-enable when auto-inject bonus feature is needed again
                                                // $merchantId = $get('merchant_id');
                                                // $period = $get('../../period');
                                                //
                                                // if ($merchantId && $period) {
                                                //     $bonusResult = self::computeBonusForMerchant($merchantId, $period, $get);
                                                //     if ($bonusResult !== null) {
                                                //         $items[] = $bonusResult;
                                                //     }
                                                // }

                                                $this->warnDuplicatePayroll($state, (string) ($get('../../period') ?? ''));
                                            }),
                                    ]),
                                Repeater::make('items')
                                    ->label('Komponen Gaji')
                                    ->addActionLabel('Tambah Komponen')
                                    ->collapsible()
                                    ->table([
                                        TableColumn::make('Komponen'),
                                        TableColumn::make('Nominal'),
                                        TableColumn::make('Jumlah'),
                                        TableColumn::make('Sub Total'),
                                    ])
                                    ->compact()
                                    ->schema([
                                        TextInput::make('component_name')
                                            ->label('Komponen')
                                            ->placeholder('contoh: Weekday/Weekend/Bonus')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('daily_rate')
                                            ->label('Nominal')
                                            ->placeholder('Masukkan nominal')
                                            ->required()
                                            ->default(0)
                                            ->prefix('Rp')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                            ->formatStateUsing(fn ($state) => format_quantity($state))
                                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                $rate = to_number($get('daily_rate'));
                                                $days = to_number($get('days'));
                                                $set('amount', $rate * $days);
                                            }),
                                        TextInput::make('days')
                                            ->label('Jumlah')
                                            ->placeholder('Masukkan jumlah')
                                            ->required()
                                            ->default(0)
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                            ->formatStateUsing(fn ($state) => format_quantity($state))
                                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                $rate = to_number($get('daily_rate'));
                                                $days = to_number($get('days'));
                                                $set('amount', $rate * $days);
                                            }),
                                        TextInput::make('amount')
                                            ->label('Sub Total')
                                            ->default(0)
                                            ->prefix('Rp')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                            ->formatStateUsing(fn ($state) => format_quantity($state))
                                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                                            ->readOnly()
                                            ->dehydrated(),
                                    ]),
                                TextEntry::make('total_gaji')
                                    ->label('Total Gaji')
                                    ->live()
                                    ->visible(fn (Get $get): bool => ! empty($get('items')))
                                    ->state(function (Get $get): HtmlString {
                                        $items = $get('items') ?? [];
                                        $total = collect($items)->sum(fn ($item) => to_number($item['amount'] ?? null));

                                        return new HtmlString(
                                            '<span style="font-size:1.125rem;font-weight:700;color:#059669;">Rp '
                                            .number_format($total, 0, ',', '.')
                                            .'</span>'
                                        );
                                    }),
                                // TODO: re-enable when attendance feature is active
                                // TextEntry::make('bonus_indicator')
                                //     ->label('Status Bonus Transaksi')
                                //     ->state(function (Get $get): HtmlString {
                                //         $merchantId = $get('merchant_id');
                                //         $period = $get('../../period');
                                //
                                //         if (! $merchantId || ! $period) {
                                //             return self::bonusIndicatorHtml('neutral', 'Pilih outlet dan periode untuk melihat bonus transaksi');
                                //         }
                                //
                                //         $bonusData = self::computeTransactionBonusData($merchantId, $period, $get);
                                //
                                //         if ($bonusData === null) {
                                //             return self::bonusIndicatorHtml('neutral', 'Pilih daftar kehadiran untuk menghitung bonus Cabang');
                                //         }
                                //
                                //         $totalBonus = $bonusData['total_bonus'];
                                //         $thresholdMetDays = $bonusData['threshold_met_days'];
                                //
                                //         if ($thresholdMetDays > 0) {
                                //             return self::bonusIndicatorHtml(
                                //                 'success',
                                //                 '✅ Bonus Transaksi: Rp '.number_format($totalBonus, 0, ',', '.').
                                //                 " ({$thresholdMetDays}/{$bonusData['total_days']} hari capai target)"
                                //             );
                                //         }
                                //
                                //         return self::bonusIndicatorHtml(
                                //             'danger',
                                //             "❌ Target cup/hari belum tercapai (0/{$bonusData['total_days']} hari)"
                                //         );
                                //     }),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Show a persistent warning notification when the selected employee already
     * has a non-canceled payroll overlapping the chosen period.
     */
    public function warnDuplicatePayroll(?string $userId, ?string $period): void
    {
        if (! $userId || ! $period) {
            return;
        }

        try {
            [$start, $end] = parse_period($period);
        } catch (\Throwable) {
            return;
        }

        $duplicates = app(PayrollDuplicateService::class)
            ->forUser((int) $userId, $start, $end);

        if ($duplicates->isEmpty()) {
            return;
        }

        $lines = $duplicates
            ->take(3)
            ->map(function (Payroll $payroll): string {
                $range = $payroll->period_start->format('d/m/Y').' - '.$payroll->period_end->format('d/m/Y');
                $total = number_format($payroll->total_amount, 0, ',', '.');

                return "• {$range} — Rp {$total} ({$payroll->status->getLabel()})";
            })
            ->implode("\n");

        $more = $duplicates->count() > 3
            ? "\n… dan ".($duplicates->count() - 3).' slip gaji lainnya.'
            : '';

        Notification::make()
            ->id('payroll-duplicate-warning')
            ->title('Karyawan sudah memiliki slip gaji pada periode ini')
            ->body("Karyawan ini sudah memiliki slip gaji yang masih aktif pada periode yang sama:\n\n{$lines}{$more}\n\nSimpan tetap bisa dilanjutkan.")
            ->warning()
            ->persistent()
            ->send();
    }

    /**
     * Render bonus recommendation tables for all selected merchants.
     * Shows per-day breakdown with cups, target, status, and bonus per person (N=1).
     */
    private static function renderBonusRecommendation(Get $get): HtmlString
    {
        $merchantIds = $get('merchant_ids');
        $period = $get('period');

        $target = (int) to_number($get('bonus_target') ?? 400);
        $base = (int) to_number($get('bonus_base_amount') ?? 5000);
        $bonusTiers = array_map(fn (array $tier) => ['step' => (int) to_number($tier['step']), 'amount' => (int) to_number($tier['amount'])], $get('bonus_tiers') ?? [['step' => 25, 'amount' => 10000]]);

        return app(PayrollBonusService::class)->renderBonusRecommendationTable($merchantIds ?? [], $period ?? '', $target, $base, $bonusTiers);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Sebagai Draft')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->action(fn () => $this->submitDraft()),
            Action::make('saveApproved')
                ->label('Simpan & Setujui')
                ->icon('heroicon-o-check-circle')
                ->action(fn () => $this->submitApproved()),
        ];
    }

    /**
     * Save all slips as Draft.
     */
    public function submitDraft(): void
    {
        $this->form->validate();

        $this->savePayrolls(PayrollStatus::Draft);
    }

    /**
     * Save all slips as Approved (existing behavior, kept for BC).
     */
    public function submit(): void
    {
        $this->form->validate();

        $this->savePayrolls(PayrollStatus::Approved);
    }

    /**
     * Save all slips as Approved.
     */
    public function submitApproved(): void
    {
        $this->form->validate();

        $this->savePayrolls(PayrollStatus::Approved);
    }

    /**
     * Persist payrolls from the form with the given status.
     */
    private function savePayrolls(PayrollStatus $status): void
    {
        $formData = $this->form->getState();

        $period = $formData['period'] ?? '';

        // Parse DateRangePicker value
        [$periodStart, $periodEnd] = parse_period($period);
        $periodStart = $periodStart->format('Y-m-d');
        $periodEnd = $periodEnd->format('Y-m-d');

        $masterComponents = $formData['master_components'] ?? [];
        $employees = $formData['employees'] ?? [];

        $created = 0;

        foreach ($employees as $employee) {
            if (empty($employee['user_id']) || empty($employee['merchant_id'])) {
                continue;
            }

            // Build merged items: master components (rate from master, days from employee) + custom-only items
            $allItems = [];
            $employeeItems = $employee['items'] ?? [];

            // Index employee items by component_name for days lookup
            $employeeItemMap = [];
            foreach ($employeeItems as $item) {
                $name = $item['component_name'] ?? '';
                if ($name !== '' && $name !== '0') {
                    $employeeItemMap[$name] = $item;
                }
            }

            // Add master components with days from employee (or 0 if not filled)
            foreach ($masterComponents as $master) {
                $name = $master['component_name'] ?? '';
                if ($name === '' || $name === '0') {
                    continue;
                }
                $rate = to_number($master['daily_rate']);
                $days = isset($employeeItemMap[$name]) ? to_number($employeeItemMap[$name]['days']) : 0;

                $allItems[] = [
                    'component_name' => $name,
                    'daily_rate' => $rate,
                    'days' => $days,
                    'amount' => $rate * $days,
                ];
            }

            // Add employee items that are NOT in master (custom components)
            foreach ($employeeItems as $item) {
                $name = $item['component_name'] ?? '';
                if ($name === '' || $name === '0') {
                    continue;
                }

                $isInMaster = collect($masterComponents)->contains(
                    fn (array $master): bool => ($master['component_name'] ?? '') === $name
                );

                if (! $isInMaster) {
                    // TODO: re-enable when attendance feature is active (auto-inject Bonus Transaksi)
                    // // Bonus Transaksi: amount is pre-computed, don't recalculate from rate * days
                    // if ($name === 'Bonus Transaksi') {
                    //     $allItems[] = [
                    //         'component_name' => $name,
                    //         'daily_rate' => 0,
                    //         'days' => to_number($item['days']),
                    //         'amount' => to_number($item['amount']),
                    //     ];
                    // } else {
                    //     $rate = to_number($item['daily_rate']);
                    //     $days = to_number($item['days']);
                    //     $allItems[] = [
                    //         'component_name' => $name,
                    //         'daily_rate' => $rate,
                    //         'days' => $days,
                    //         'amount' => $rate * $days,
                    //     ];
                    // }

                    $rate = to_number($item['daily_rate']);
                    $days = to_number($item['days']);
                    $calculatedAmount = $rate * $days;
                    $existingAmount = to_number($item['amount']);
                    $allItems[] = [
                        'component_name' => $name,
                        'daily_rate' => $rate,
                        'days' => $days,
                        'amount' => $calculatedAmount != 0.0 ? $calculatedAmount : $existingAmount,
                    ];
                }
            }

            if (empty($allItems)) {
                continue;
            }

            $totalAmount = collect($allItems)->sum('amount');

            $bonusTiers = array_map(fn (array $t) => ['step' => (int) to_number($t['step']), 'amount' => (int) to_number($t['amount'])], $formData['bonus_tiers'] ?? [['step' => 25, 'amount' => 10000]]);

            $payroll = Payroll::query()->create([
                'merchant_id' => $employee['merchant_id'],
                'user_id' => $employee['user_id'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => $status,
                'total_amount' => $totalAmount,
                'bonus_target' => to_number($formData['bonus_target'] ?? 400),
                'bonus_base_amount' => to_number($formData['bonus_base_amount'] ?? 5000),
                'bonus_tiers' => $bonusTiers,
                'created_by' => auth()->id(),
            ]);

            foreach ($allItems as $item) {
                $payroll->items()->create([
                    'component_name' => $item['component_name'],
                    'daily_rate' => $item['daily_rate'],
                    'days' => $item['days'],
                    'amount' => $item['amount'],
                ]);
            }

            $created++;
        }

        $statusLabel = $status->getLabel();
        $tone = $status === PayrollStatus::Draft ? 'warning' : 'success';

        Notification::make()
            ->title("Slip gaji berhasil dibuat ({$statusLabel})")
            ->body("{$created} slip gaji karyawan telah dibuat untuk periode {$periodStart} - {$periodEnd}.")
            ->{$tone}()
            ->send();

        $this->redirect(PayrollResource::getUrl('index'));
    }
}
