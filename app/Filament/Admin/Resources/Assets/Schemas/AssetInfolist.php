<?php

namespace App\Filament\Admin\Resources\Assets\Schemas;

use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Aset')
                    ->description('Data aset')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Aset'),
                        TextEntry::make('item.name')
                            ->label('Dari Item')
                            ->visible(fn (Asset $record): bool => $record->item_id !== null),
                        TextEntry::make('acquisition_date')
                            ->label('Tanggal Akuisisi')
                            ->date(),
                        TextEntry::make('acquisition_cost')
                            ->label('Harga Akuisisi')
                            ->numeric(),
                        TextEntry::make('depreciation_method')
                            ->label('Metode Penyusutan')
                            ->badge()
                            ->visible(fn (Asset $record): bool => $record->depreciation_method !== DepreciationMethod::NonDepreciable),
                        TextEntry::make('annual_depreciation_rate')
                            ->label('Nilai / Tahun')
                            ->suffix('%')
                            ->state(fn (Asset $record): string => number_format($record->annual_depreciation_rate, 2, ',', '.')),
                        TextEntry::make('useful_life_months')
                            ->label('Umur Ekonomis')
                            ->suffix(' bulan')
                            ->visible(fn (Asset $record): bool => $record->useful_life_months > 0),
                        TextEntry::make('monthly_depreciation')
                            ->label('Depresiasi/Bln')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->monthly_depreciation),
                        TextEntry::make('accumulated_depreciation')
                            ->label('Akumulasi')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->accumulated_depreciation),
                        TextEntry::make('current_book_value')
                            ->label('Nilai Buku')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->current_book_value),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                // Section::make('Riwayat Depresiasi')
                //     ->schema([
                //         RepeatableEntry::make('depreciations')
                //             ->label('Riwayat')
                //             ->table([
                //                 TableColumn::make('Periode'),
                //                 TableColumn::make('Jumlah'),
                //                 TableColumn::make('Nilai Sebelum'),
                //                 TableColumn::make('Nilai Sesudah'),
                //             ])
                //             ->schema([
                //                 TextEntry::make('period_date')
                //                     ->label('Periode')
                //                     ->date(),
                //                 TextEntry::make('depreciation_amount')
                //                     ->label('Jumlah')
                //                     ->numeric(),
                //                 TextEntry::make('book_value_before')
                //                     ->label('Nilai Sebelum')
                //                     ->numeric(),
                //                 TextEntry::make('book_value_after')
                //                     ->label('Nilai Sesudah')
                //                     ->numeric(),
                //             ]),
                //     ]),
                // Section::make('Waktu')
                //     ->schema([
                //         TextEntry::make('created_at')
                //             ->label('Dibuat')
                //             ->dateTime(),
                //         TextEntry::make('updated_at')
                //             ->label('Diperbarui')
                //             ->dateTime(),
                //     ])
                //     ->columns([
                //         'sm' => 1,
                //         'md' => 2,
                //     ]),
            ]);
    }
}
