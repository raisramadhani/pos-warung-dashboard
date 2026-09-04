<?php

namespace App\Filament\Admin\Pages;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Admin\Resources\Assets\AssetResource;
use App\Models\Inventories\Asset;
use App\Services\AssetDepreciationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DepreciationSchedule extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Jadwal Penyusutan';

    protected static ?string $navigationParentItem = AssetResource::class;

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.depreciation-schedule';

    /**
     * Periode pembukuan berjalan (awal bulan saat ini).
     * Digunakan untuk menampilkan dan menerapkan penyusutan periode ini.
     */
    public function getPeriodDate(): Carbon
    {
        return now()->startOfMonth();
    }

    public function getHeading(): string|Htmlable
    {
        return 'Jadwal Penyusutan Aset';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Daftar aset yang memiliki periode penyusutan terbuka. Terapkan penyusutan secara urut dari periode terdahulu.';
    }

    /**
     * Sumber data halaman Jadwal Penyusutan.
     *
     * Halaman ini menampilkan aset yang memiliki periode penyusutan tertunggak/terbuka,
     * yaitu aset dengan kriteria:
     * 1. Status Active (bukan Dibuang/Dijual maupun tersusutkan penuh),
     * 2. Aset yang disusutkan (depreciation_method bukan NonDepreciable),
     * 3. Memiliki masa manfaat (useful_life_months > 0),
     * 4. Sudah melewati bulan akuisisi (acquisition_date < periode) — penyusutan dimulai
     *    bulan SETELAH bulan akuisisi untuk aset baru,
     * 5. Belum pernah disusutkan untuk periode ini (last_depreciation_date null atau < periode).
     *
     * Kolom "Periode" menampilkan nextPendingPeriod per aset (periode tertua yang belum
     * tercatat) — bukan bulan berjalan. Saat admin lupa meng-apply sebuah bulan, aset
     * tetap tampil dan wajib disusutkan dari periode tertua dulu (aturan wajib urut).
     * Badge "Tertunggak" muncul bila periode tersebut berada di bawah bulan berjalan.
     *
     * Klik "Terapkan" (single) memanggil AssetDepreciationService::apply() untuk periode
     * tertua aset; "Terapkan Massal" memanggil applyNext(). Data disimpan ke tabel
     * asset_depreciations, nilai buku & status aset ikut diperbarui, lalu barisnya bergeser
     * ke periode berikutnya (atau hilang bila sudah tidak ada kerjaan).
     */
    public function getTableQuery(): Builder
    {
        return Asset::query()
            ->where('status', AssetStatus::Active)
            ->where('depreciation_method', '!=', DepreciationMethod::NonDepreciable)
            ->where('useful_life_months', '>', 0)
            ->where('acquisition_date', '<', $this->getPeriodDate())
            ->where(function (Builder $query) {
                $query->whereNull('last_depreciation_date')
                    ->orWhere('last_depreciation_date', '<', $this->getPeriodDate());
            });
    }

    public function table(Table $table): Table
    {
        $period = $this->getPeriodDate();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pending_period')
                    ->label('Periode')
                    ->state(fn (Asset $record): ?Carbon => app(AssetDepreciationService::class)->nextPendingPeriod($record, $period))
                    ->formatStateUsing(fn (?Carbon $state): string => $state?->translatedFormat('F Y') ?? '-'),
                TextColumn::make('annual_depreciation_rate')
                    ->label('Nilai (%)')
                    ->suffix('%')
                    ->state(fn (Asset $record): string => number_format($record->annual_depreciation_rate, 2, ',', '.')),
                TextColumn::make('depreciation_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (DepreciationMethod $state): string => $state->getLabel()),
                TextColumn::make('overdue')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Asset $record): string => app(AssetDepreciationService::class)->nextPendingPeriod($record, $period) !== null
                        && app(AssetDepreciationService::class)->nextPendingPeriod($record, $period)->lt($period)
                        ? 'Tertunggak'
                        : 'Berjalan')
                    ->color(fn (string $state): string => $state === 'Tertunggak' ? 'danger' : 'success'),
                TextColumn::make('projected_amount')
                    ->label('Nilai Penyusutan')
                    ->numeric()
                    ->state(fn (Asset $record): float => $this->pendingAmount($record, $period)),
            ])
            ->actions([
                Action::make('apply')
                    ->label('Terapkan')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(fn (Asset $record) => $this->applySchedule($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('apply_mass')
                        ->label('Terapkan Massal')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function () {
                            $count = app(AssetDepreciationService::class)->applyNext(
                                $this->getSelectedTableRecords(),
                                $this->getPeriodDate(),
                            );

                            Notification::make()
                                ->title("Penyusutan diterapkan untuk {$count} aset")
                                ->success()
                                ->send();

                            $this->resetTable();
                        }),
                ]),
            ]);
    }

    public function applySchedule(Asset $record): void
    {
        $pending = app(AssetDepreciationService::class)->nextPendingPeriod($record, $this->getPeriodDate());

        $applied = $pending !== null
            && app(AssetDepreciationService::class)->apply($record, $pending) !== null;

        Notification::make()
            ->title($applied ? 'Penyusutan diterapkan' : 'Tidak ada penyusutan untuk periode ini')
            ->{$applied ? 'success' : 'warning'}()
            ->send();

        $this->resetTable();
    }

    private function pendingAmount(Asset $record, Carbon $period): float
    {
        $pending = app(AssetDepreciationService::class)->nextPendingPeriod($record, $period);

        return $pending === null
            ? 0
            : app(AssetDepreciationService::class)->monthlyAmountForPeriod($record, $pending);
    }
}
