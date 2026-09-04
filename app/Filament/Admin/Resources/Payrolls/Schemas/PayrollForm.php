<?php

namespace App\Filament\Admin\Resources\Payrolls\Schemas;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
// use App\Models\Attendances\AttendanceSheet; // TODO: re-enable when attendance feature is active
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use App\Services\PayrollBonusService;
use App\Services\PayrollDuplicateService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Slip Gaji')
                    ->description('Pilih tipe kepemilikan, outlet, periode penggajian dan nama karyawan')
                    ->schema([
                        Grid::make(10)
                            ->columnSpanFull()
                            ->schema([
                                ToggleButtons::make('ownership_type')
                                    ->label('Tipe Kepemilikan')
                                    ->options(OwnershipType::class)
                                    ->default(OwnershipType::Main->value)
                                    ->grouped()
                                    ->nullable()
                                    ->inline()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('merchant_id', null);
                                        $set('user_id', null);
                                    })
                                    ->columnSpan(2),
                                DateRangePicker::make('period')
                                    ->label('Periode')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->placeholder('Pilih rentang periode')
                                    ->nullable()
                                    ->autoApply()
                                    ->live()
                                    ->columnSpan(4),
                                Select::make('merchant_id')
                                    ->label('Outlet')
                                    ->prefixIcon('heroicon-o-building-storefront')
                                    ->options(function (Get $get) {
                                        $ownershipType = $get('ownership_type');

                                        return Merchant::query()
                                            ->where('type', MerchantType::Merchant)
                                            ->where('current_status', MerchantStatus::Active)
                                            ->when($ownershipType, fn ($query) => $query->where('ownership_type', $ownershipType))
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (Merchant $m) => [$m->id => "{$m->name} - {$m->ownership_type->getLabel()}"]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Pilih outlet')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $set('user_id', null);
                                    })
                                    ->columnSpan(4),
                            ]),
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
                            ->afterStateUpdated(function (Get $get, ?string $state): void {
                                if (! $state) {
                                    return;
                                }

                                $period = (string) ($get('period') ?? '');

                                if (! $period) {
                                    return;
                                }

                                try {
                                    [$start, $end] = parse_period($period);
                                } catch (\Throwable) {
                                    return;
                                }

                                $duplicates = app(PayrollDuplicateService::class)
                                    ->forUser((int) $state, $start, $end);

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
                            }),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan tambahan')
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('Pengaturan Bonus Transaksi')
                    ->description('Konfigurasi target dan bonus transaksi')
                    ->schema([
                        TextInput::make('bonus_target')
                            ->label('Target Transaksi')
                            ->nullable()
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
                            ->nullable()
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
                    ->description('Data transaksi harian dan rekomendasi nominal bonus per orang. Masukkan manual di komponen gaji.')
                    ->schema([
                        TextEntry::make('bonus_recommendation')
                            ->label('')
                            ->columnSpanFull()
                            ->state(fn (Get $get): HtmlString => self::renderBonusRecommendation($get)),
                    ]),
                Section::make('Komponen Gaji')
                    ->description('Daftar komponen gaji untuk karyawan ini')
                    ->schema([
                        Repeater::make('items')
                            ->label('Komponen')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->collapsible()
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::mutateItemData($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::mutateItemData($data))
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
                                    ->label('Jumlah')
                                    ->placeholder('Jumlah otomatis')
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
                    ]),
            ]);
    }

    /**
     * Mutate item data — preserve pre-set amount (e.g. Bonus Transaksi) instead of recalculating.
     * Negative amounts (potongan gaji) are valid.
     */
    private static function mutateItemData(array $data): array
    {
        $rate = to_number($data['daily_rate'] ?? null);
        $days = to_number($data['days'] ?? null);
        $calculatedAmount = $rate * $days;
        $existingAmount = to_number($data['amount'] ?? null);

        return [
            ...$data,
            'daily_rate' => $rate,
            'days' => $days,
            'amount' => $calculatedAmount != 0.0 ? $calculatedAmount : $existingAmount,
        ];
    }

    /**
     * Render bonus recommendation table (per-day breakdown).
     * Shows only days where threshold is met. Uses N=1 (per-person amount).
     */
    private static function renderBonusRecommendation(Get $get): HtmlString
    {
        $merchantId = $get('merchant_id');
        $period = $get('period');

        if (! $merchantId || ! $period) {
            return new HtmlString('<p style="color:#94a3b8;font-size:.875rem;">Pilih outlet dan periode untuk melihat rekomendasi bonus.</p>');
        }

        $target = (int) to_number($get('bonus_target') ?? 400);
        $base = (int) to_number($get('bonus_base_amount') ?? 5000);
        $bonusTiers = array_map(fn (array $tier) => ['step' => (int) to_number($tier['step']), 'amount' => (int) to_number($tier['amount'])], $get('bonus_tiers') ?? [['step' => 25, 'amount' => 10000]]);

        return app(PayrollBonusService::class)->renderBonusRecommendationTable($merchantId, $period, $target, $base, $bonusTiers);
    }

    /**
     * Inject "Bonus Pencapaian" item when merchant + period are set and bonus threshold is met.
     * Uses N=1 (per-person amount). Total bonus becomes daily_rate, days=1.
     *
     * TODO: re-enable when auto-inject bonus feature is needed again
     */
    // private static function injectBonusIfApplicable(Set $set, Get $get): void
    // {
    //     $merchantId = $get('merchant_id');
    //     $period = $get('period');
    //
    //     if (! $merchantId || ! $period) {
    //         return;
    //     }
    //
    //     $bonusItem = self::computeBonusPencapaian($merchantId, $period, $get);
    //
    //     $items = $get('items') ?? [];
    //     $filtered = collect($items)
    //         ->filter(fn (array $item) => ($item['component_name'] ?? '') !== 'Bonus Pencapaian')
    //         ->values()
    //         ->all();
    //
    //     if ($bonusItem !== null) {
    //         $filtered[] = $bonusItem;
    //     }
    //
    //     $set('items', $filtered);
    // }

    /**
     * Compute "Bonus Pencapaian" item. Returns null if no days meet threshold.
     *
     * @return array{component_name: string, daily_rate: int, days: int, amount: int}|null
     *
     * TODO: re-enable when auto-inject bonus feature is needed again
     */
    // private static function computeBonusPencapaian(int $merchantId, string $period, Get $get): ?array
    // {
    //     $target = (int) to_number($get('bonus_target') ?? 400);
    //     $base = (int) to_number($get('bonus_base_amount') ?? 5000);
    //     $bonusTiers = array_map(fn (array $tier) => ['step' => (int) to_number($tier['step']), 'amount' => (int) to_number($tier['amount'])], $get('bonus_tiers') ?? [['step' => 25, 'amount' => 10000]]);
    //
    //     return app(PayrollBonusService::class)->computeBonusItem($merchantId, $period, $target, $base, $bonusTiers);
    // }
}
