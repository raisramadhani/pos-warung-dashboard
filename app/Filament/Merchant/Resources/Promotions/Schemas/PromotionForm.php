<?php

namespace App\Filament\Merchant\Resources\Promotions\Schemas;

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Promo')
                    ->description('Data utama promo')
                    ->columns([
                        'sm' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Promo')
                            ->placeholder('Masukan nama promo')
                            ->required()
                            ->maxLength(255),
                        DateRangePicker::make('date_range')
                            ->label('Periode Promo')
                            ->placeholder('Pilih rentang tanggal')
                            ->helperText('Kosong = Tanpa batas waktu')
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->nullable()
                            ->autoApply()
                            ->live()
                            ->disableClear(false)
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state === null || $state === '') {
                                    $set('starts_at', null);
                                    $set('ends_at', null);

                                    return;
                                }

                                if (\is_array($state)) {
                                    $start = $state[0] ?? null;
                                    $end = $state[1] ?? null;
                                    $set('starts_at', $start !== '' ? $start : null);
                                    $set('ends_at', $end !== '' ? $end : null);

                                    return;
                                }

                                $parts = \is_string($state) ? explode(' - ', $state, 2) : [null, null];
                                $start = trim((string) ($parts[0] ?? ''));
                                $end = trim((string) ($parts[1] ?? ''));
                                $set('starts_at', $start !== '' ? $start : null);
                                $set('ends_at', $end !== '' ? $end : null);
                            }),
                        Select::make('type')
                            ->label('Tipe Promo')
                            ->options(PromotionType::class)
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): string => PromotionType::descriptionFor($get('type')))
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                $set('description', PromotionType::descriptionFor($state));
                                if ($state) {
                                    $typeEnum = $state instanceof PromotionType
                                        ? $state
                                        : (\is_string($state) ? PromotionType::tryFrom($state) : null);

                                    if ($typeEnum) {
                                        $rewardType = $typeEnum->defaultRewardType()->value;
                                        $rewards = $get('rewards') ?? [];

                                        if (\count($rewards) === 0) {
                                            $set('rewards', [
                                                ['reward_type' => $rewardType],
                                            ]);
                                        } else {
                                            $keys = array_keys($rewards);
                                            $firstKey = $keys[0];
                                            $set("rewards.{$firstKey}.reward_type", $rewardType);

                                            if ($rewardType !== PromotionRewardType::FreeItem->value) {
                                                $set("rewards.{$firstKey}.quantity", null);
                                                $set("rewards.{$firstKey}.product_id", null);
                                            } else {
                                                $set("rewards.{$firstKey}.value", null);
                                            }
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Masukan deskripsi promo')
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('starts_at')->hidden(),
                        TextInput::make('ends_at')->hidden(),
                    ]),
                Section::make('Syarat (Beli)')
                    ->description('Produk atau kategori yang harus dibeli beserta jumlah minimumnya')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('conditions')
                            ->label('Syarat')
                            ->relationship()
                            ->collapsible(false)
                            ->default([])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->required()
                            ->columns(3)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->helperText('Produk dengan promo aktif ditandai dan tidak bisa dipilih.')
                                    ->options(function (Get $get): array {
                                        $merchantId = self::resolveMerchantId();

                                        if ($merchantId === null) {
                                            return [];
                                        }

                                        $currentPromotionId = self::resolveCurrentPromotionId();
                                        $blockedProductIds = self::blockedProductIds($merchantId, $currentPromotionId);
                                        $selectedProductId = (int) ($get('product_id') ?? 0);

                                        return Product::query()
                                            ->where('merchant_id', $merchantId)
                                            ->orderBy('name')
                                            ->get(['id', 'name'])
                                            ->mapWithKeys(fn (Product $product): array => [
                                                (string) $product->id => \in_array($product->id, $blockedProductIds, true) && $product->id !== $selectedProductId
                                                    ? $product->name.' (sudah ada promo aktif)'
                                                    : $product->name,
                                            ])
                                            ->all();
                                    })
                                    ->disableOptionWhen(function (string $value, Get $get): bool {
                                        $merchantId = self::resolveMerchantId();

                                        if ($merchantId === null) {
                                            return false;
                                        }

                                        $currentPromotionId = self::resolveCurrentPromotionId();
                                        $blockedProductIds = self::blockedProductIds($merchantId, $currentPromotionId);
                                        $selectedProductId = (int) ($get('product_id') ?? 0);

                                        return \in_array((int) $value, $blockedProductIds, true) && (int) $value !== $selectedProductId;
                                    }),
                                // Select::make('category_id')
                                //     ->label('Kategori')
                                //     ->relationship('category', 'name')
                                //     ->searchable()
                                //     ->preload()
                                //     ->nullable()
                                //     ->live()
                                //     ->afterStateUpdated(function (Set $set, ?string $state): void {
                                //         if ($state !== null && $state !== '') {
                                //             $set('product_id', null);
                                //         }
                                //     }),
                                TextInput::make('min_quantity')
                                    ->label('Jumlah Minimum')
                                    ->placeholder('1')
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Hadiah (Dapat)')
                    ->description('Hadiah yang diberikan ketika syarat terpenuhi')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('rewards')
                            ->label('Hadiah')
                            ->relationship()
                            ->collapsible(false)
                            ->default([])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->required()
                            ->columns(3)
                            ->schema([
                                Select::make('reward_type')
                                    ->label('Jenis Hadiah')
                                    ->options(PromotionRewardType::class)
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Select::make('product_id')
                                    ->label('Produk (gratis)')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->dehydrated(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::FreeItem->value)
                                    ->visible(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::FreeItem->value)
                                    ->helperText('Kosongkan = produk yang sama dengan syarat beli.'),
                                TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->placeholder('1')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(function ($state) {
                                        if ($state === null || $state === '' || $state === 0) {
                                            return null;
                                        }

                                        return (int) to_number($state);
                                    })
                                    ->dehydrated(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::FreeItem->value)
                                    ->minValue(1)
                                    ->nullable()
                                    ->visible(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::FreeItem->value)
                                    ->required(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::FreeItem->value)
                                    ->helperText('Jumlah item gratis yang diberikan.'),
                                TextInput::make('value')
                                    ->label('Nilai')
                                    ->placeholder('0')
                                    ->minValue(0)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->dehydrated(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) !== PromotionRewardType::FreeItem->value)
                                    ->nullable()
                                    ->visible(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) !== PromotionRewardType::FreeItem->value)
                                    ->required(fn (Get $get): bool => self::normalizeRewardType($get('reward_type')) !== PromotionRewardType::FreeItem->value)
                                    ->prefix(fn (Get $get): string => match (self::normalizeRewardType($get('reward_type'))) {
                                        PromotionRewardType::PercentDiscount->value => '',
                                        default => 'Rp',
                                    })
                                    ->suffix(fn (Get $get): string => self::normalizeRewardType($get('reward_type')) === PromotionRewardType::PercentDiscount->value ? '%' : '')
                                    ->helperText(fn (Get $get): string => match (self::normalizeRewardType($get('reward_type'))) {
                                        PromotionRewardType::FixedPrice->value => 'Total harga pas untuk jumlah item sesuai syarat beli.',
                                        PromotionRewardType::PercentDiscount->value => 'Persen potongan (contoh: 20 = 20%)',
                                        PromotionRewardType::FixedDiscount->value => 'Nominal potongan rupiah',
                                        default => 'Nilai hadiah',
                                    }),
                            ]),
                    ]),
                Section::make('Jadwal Aktif')
                    ->description('Jendela waktu promo berlaku. Kosongkan semua = berlaku kapan saja')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('schedules')
                            ->label('Jadwal')
                            ->relationship()
                            ->collapsible()
                            ->default([])
                            ->columns(3)
                            ->table([
                                TableColumn::make('Hari'),
                                TableColumn::make('Jam Mulai'),
                                TableColumn::make('Jam Selesai'),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('Hari')
                                    ->placeholder('Setiap hari')
                                    ->options([
                                        1 => 'Senin',
                                        2 => 'Selasa',
                                        3 => 'Rabu',
                                        4 => 'Kamis',
                                        5 => 'Jumat',
                                        6 => 'Sabtu',
                                        7 => 'Minggu',
                                    ])
                                    ->nullable()
                                    ->native(false),
                                TimePicker::make('start_time')
                                    ->label('Jam Mulai')
                                    ->placeholder('00:00')
                                    ->nullable()
                                    ->native(false)
                                    ->seconds(false),
                                TimePicker::make('end_time')
                                    ->label('Jam Selesai')
                                    ->placeholder('23:59')
                                    ->nullable()
                                    ->native(false)
                                    ->seconds(false),
                            ]),
                    ]),
            ]);
    }

    private static function resolveMerchantId(): ?int
    {
        $tenantId = filament()->getTenant()?->getKey();

        if ($tenantId !== null) {
            return (int) $tenantId;
        }

        $tenantRouteParam = request()->route('tenant');

        if (is_numeric($tenantRouteParam)) {
            return (int) $tenantRouteParam;
        }

        if ($tenantRouteParam instanceof Model) {
            return (int) $tenantRouteParam->getKey();
        }

        return null;
    }

    private static function resolveCurrentPromotionId(): ?int
    {
        $record = request()->route('record');

        if ($record instanceof Promotion) {
            return (int) $record->getKey();
        }

        if (is_numeric($record)) {
            return (int) $record;
        }

        if (\is_string($record) && $record !== '') {
            $merchantId = self::resolveMerchantId();

            if ($merchantId === null) {
                return null;
            }

            return Promotion::query()
                ->where('merchant_id', $merchantId)
                ->where('slug', $record)
                ->value('id');
        }

        return null;
    }

    private static function normalizeRewardType(mixed $value): ?string
    {
        if ($value instanceof PromotionRewardType) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array<int>
     */
    private static function blockedProductIds(int $merchantId, ?int $currentPromotionId = null): array
    {
        $query = Promotion::query()
            ->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->whereHas('conditions', fn ($builder) => $builder->whereNotNull('product_id'));

        if ($currentPromotionId !== null) {
            $query->where('id', '!=', $currentPromotionId);
        }

        return $query
            ->with('conditions')
            ->get()
            ->flatMap(fn (Promotion $promotion) => $promotion->conditions->pluck('product_id'))
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
