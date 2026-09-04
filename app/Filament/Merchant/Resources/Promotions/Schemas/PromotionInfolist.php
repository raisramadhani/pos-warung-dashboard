<?php

namespace App\Filament\Merchant\Resources\Promotions\Schemas;

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Promotions\PromotionCondition;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Promo')
                    ->description('Data utama promo')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Promo'),
                        TextEntry::make('type')
                            ->label('Tipe Promo')
                            ->formatStateUsing(function (mixed $state): string {
                                $type = $state instanceof PromotionType ? $state : PromotionType::tryFrom((string) $state);

                                return $type?->getLabel() ?? '-';
                            })
                            ->badge(),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean()
                            ->tooltip(fn ($state): string => $state ? 'Promo aktif' : 'Promo tidak aktif'),
                        TextEntry::make('starts_at')
                            ->label('Mulai Berlaku')
                            ->placeholder('-')
                            ->dateTime('l, d F Y H:i'),
                        TextEntry::make('ends_at')
                            ->label('Berakhir')
                            ->placeholder('-')
                            ->dateTime('l, d F Y H:i'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('redemptions_count')
                            ->label('Total Pemakaian')
                            ->numeric()
                            ->formatStateUsing(fn ($state): string => format_quantity($state))
                            ->badge(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),

                Section::make('Syarat (Beli)')
                    ->description('Syarat pembelian promo')
                    ->schema([
                        RepeatableEntry::make('conditions')
                            ->label('Syarat')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Produk')
                                    ->placeholder('-'),
                                // Tidak dibutuhkan
                                // TextEntry::make('category.name')
                                //     ->label('Kategori')
                                //     ->placeholder('-'),
                                TextEntry::make('min_quantity')
                                    ->label('Jumlah Minimum')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                                    ->suffix(' item'),
                                TextEntry::make('condition_note')
                                    ->label('Keterangan')
                                    ->state(function (PromotionCondition $record): string {
                                        $quantity = format_quantity($record->min_quantity ?? 0);

                                        if ($record->product_id !== null) {
                                            return 'Berlaku saat pembelian minimal '.$quantity.' item produk ini.';
                                        }

                                        if ($record->category_id !== null) {
                                            return 'Berlaku saat pembelian minimal '.$quantity.' item dari kategori ini.';
                                        }

                                        return 'Atur produk atau kategori agar syarat promo jelas.';
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Hadiah (Dapat)')
                    ->description('Hadiah yang diberikan ketika syarat terpenuhi')
                    ->schema([
                        RepeatableEntry::make('rewards')
                            ->label('Hadiah')
                            ->schema([
                                TextEntry::make('reward_type')
                                    ->label('Jenis Hadiah')
                                    ->formatStateUsing(function (mixed $state): string {
                                        $rewardType = $state instanceof PromotionRewardType ? $state : PromotionRewardType::tryFrom((string) $state);

                                        return $rewardType?->getLabel() ?? '-';
                                    })
                                    ->badge(),
                                TextEntry::make('product.name')
                                    ->label('Produk')
                                    ->state(function ($record): string {
                                        $productName = $record?->product?->name;

                                        if ($productName !== null && $productName !== '') {
                                            return $productName;
                                        }

                                        return 'Mengikuti produk syarat beli';
                                    })
                                    ->visible(fn ($record): bool => self::normalizeRewardType($record?->reward_type) === PromotionRewardType::FreeItem)
                                    ->placeholder('-'),
                                TextEntry::make('quantity')
                                    ->label('Jumlah')
                                    ->placeholder('-')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                                    ->suffix(' item')
                                    ->visible(fn ($record): bool => self::normalizeRewardType($record?->reward_type) === PromotionRewardType::FreeItem),
                                TextEntry::make('value')
                                    ->label('Nilai')
                                    ->placeholder('-')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                                    ->prefix(fn ($record): string => \in_array(self::normalizeRewardType($record?->reward_type), [
                                        PromotionRewardType::FixedPrice,
                                        PromotionRewardType::FixedDiscount,
                                    ], true) ? 'Rp ' : '')
                                    ->suffix(fn ($record): string => self::normalizeRewardType($record?->reward_type) === PromotionRewardType::PercentDiscount ? '%' : '')
                                    ->visible(fn ($record): bool => self::normalizeRewardType($record?->reward_type) !== PromotionRewardType::FreeItem),
                                TextEntry::make('reward_note')
                                    ->label('Keterangan')
                                    ->state(function ($record): string {
                                        return match (self::normalizeRewardType($record?->reward_type)) {
                                            PromotionRewardType::FreeItem => 'Pelanggan akan mendapatkan item gratis sesuai jumlah di atas.',
                                            PromotionRewardType::FixedPrice => 'Total harga khusus untuk jumlah minimum pada syarat beli.',
                                            PromotionRewardType::PercentDiscount => 'Potongan dihitung dalam persen dari harga normal produk.',
                                            PromotionRewardType::FixedDiscount => 'Potongan nominal rupiah dari harga normal produk.',
                                            default => '-',
                                        };
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Jadwal Aktif')
                    ->description('Jendela waktu promo berlaku')
                    ->visible(fn ($record): bool => $record !== null && $record->schedules->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('schedules')
                            ->label('Jadwal')
                            ->schema([
                                TextEntry::make('day_of_week')
                                    ->label('Hari')
                                    ->state(fn ($record): string => match ($record?->day_of_week) {
                                        1 => 'Senin',
                                        2 => 'Selasa',
                                        3 => 'Rabu',
                                        4 => 'Kamis',
                                        5 => 'Jumat',
                                        6 => 'Sabtu',
                                        7 => 'Minggu',
                                        default => 'Setiap hari',
                                    }),
                                TextEntry::make('start_time')
                                    ->label('Jam Mulai')
                                    ->placeholder('-'),
                                TextEntry::make('end_time')
                                    ->label('Jam Selesai')
                                    ->placeholder('-'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    private static function normalizeRewardType(mixed $value): ?PromotionRewardType
    {
        if ($value instanceof PromotionRewardType) {
            return $value;
        }

        if (\is_string($value)) {
            return PromotionRewardType::tryFrom($value);
        }

        return null;
    }
}
