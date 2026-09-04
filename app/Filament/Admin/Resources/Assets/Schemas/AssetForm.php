<?php

namespace App\Filament\Admin\Resources\Assets\Schemas;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Aset')
                    ->description('Data aset dan akuisisi')
                    ->schema([
                        DatePicker::make('acquisition_date')
                            ->label('Tanggal Akuisisi')
                            ->default(now())
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Aset')
                            ->placeholder('Masukan nama aset')
                            ->required()
                            ->maxLength(255),
                        Select::make('item_id')
                            ->label('Terikat Item (Opsional)')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Pilih item alat agar aset terikat untuk keperluan distribusi'),
                        TextInput::make('acquisition_cost')
                            ->label('Harga Akuisisi')
                            ->placeholder('Masukan harga akuisisi')
                            ->required()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state)),
                        Toggle::make('is_non_depreciable')
                            ->label('Aset Non Depresiasi')
                            ->default(true)
                            ->live()
                            ->helperText('Centang bila aset tidak mengalami penyusutan nilai')
                            ->afterStateHydrated(function (Toggle $component, ?Asset $record) {
                                $component->state($record
                                    ? $record->depreciation_method === DepreciationMethod::NonDepreciable
                                    : true);
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, bool $state) {
                                if (! $state && $get('depreciation_method') === DepreciationMethod::NonDepreciable->value) {
                                    $set('depreciation_method', DepreciationMethod::StraightLine->value);
                                }
                            }),
                        Select::make('status')
                            ->label('Status')
                            ->options(AssetStatus::class)
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(2),
                Section::make('Penyusutan')
                    ->description('Aturan penyusutan aset')
                    ->visible(fn (Get $get): bool => ! $get('is_non_depreciable'))
                    ->schema([
                        Select::make('depreciation_method')
                            ->label('Metode Penyusutan')
                            ->options(static fn (): array => collect(DepreciationMethod::cases())
                                ->reject(fn (DepreciationMethod $method) => $method === DepreciationMethod::NonDepreciable)
                                ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->getLabel()])
                                ->all())
                            ->default(DepreciationMethod::NonDepreciable->value)
                            ->required()
                            ->live(),
                        Select::make('useful_life_unit')
                            ->label('Satuan Masa Manfaat')
                            ->options([
                                'tahun' => 'Tahun',
                                'bulan' => 'Bulan',
                            ])
                            ->default('tahun')
                            ->required()
                            ->live()
                            ->afterStateHydrated(fn (Select $component, ?Asset $record) => $component->state(self::hydrateUnit($record))),
                        TextInput::make('useful_life_value')
                            ->label('Masa Manfaat')
                            ->placeholder('Masukan masa manfaat')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffix(fn (Get $get): string => $get('useful_life_unit') === 'bulan' ? 'bulan' : 'tahun')
                            ->helperText(fn (Get $get): string => 'Nilai / Tahun: '.self::annualRate($get).'. Untuk aset lama yang dimasukkan hari ini, pastikan akumulasi penyusutan sebelumnya sudah dijurnal terpisah di Saldo Awal Akuntansi.')
                            ->live()
                            ->afterStateHydrated(fn (TextInput $component, ?Asset $record) => $component->state(self::hydrateValue($record))),
                    ])
                    ->columns(2),
                Section::make('Informasi Tambahan')
                    ->schema([
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Masukan deskripsi')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function annualRate(Get $get): string
    {
        $value = (float) $get('useful_life_value');

        if ($value <= 0) {
            return '-';
        }

        $months = $get('useful_life_unit') === 'bulan' ? round($value) : round($value * 12);
        $factor = $get('depreciation_method') === DepreciationMethod::ReduceBalance->value ? 2400 : 1200;

        return number_format($factor / max(1, $months), 2, ',', '.').'%';
    }

    private static function hydrateUnit(?Asset $record): string
    {
        if ($record === null) {
            return 'tahun';
        }

        $months = (int) ($record->useful_life_months ?? 0);

        return ($months > 0 && $months % 12 === 0) ? 'tahun' : 'bulan';
    }

    private static function hydrateValue(?Asset $record): ?float
    {
        $months = (int) ($record->useful_life_months ?? 0);

        if ($months <= 0) {
            return null;
        }

        return ($months % 12 === 0) ? round($months / 12, 2) : $months;
    }
}
