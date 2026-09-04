<?php

namespace App\Filament\Merchant\Resources\Assets\Schemas;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
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
                    ->description('Detail aset alat di outlet')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Aset'),
                        TextEntry::make('item.name')
                            ->label('Item')
                            ->placeholder('-'),
                        TextEntry::make('acquisition_date')
                            ->label('Tgl Akuisisi')
                            ->date(),
                        TextEntry::make('acquisition_cost')
                            ->label('Harga Akuisisi')
                            ->numeric(),
                        TextEntry::make('depreciation_method')
                            ->label('Metode Penyusutan')
                            ->badge()
                            ->formatStateUsing(fn (DepreciationMethod $state): string => $state->getLabel())
                            ->color(fn (DepreciationMethod $state): string => $state->getColor()),
                        TextEntry::make('useful_life_months')
                            ->label('Umur Manfaat')
                            ->suffix(' bulan')
                            ->placeholder('-'),
                        TextEntry::make('annual_depreciation_rate')
                            ->label('Nilai / Tahun')
                            ->suffix('%')
                            ->formatStateUsing(fn (Asset $record): string => number_format($record->annual_depreciation_rate, 2, ',', '.')),
                        TextEntry::make('monthly_depreciation')
                            ->label('Depresiasi / Bulan')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->monthly_depreciation),
                        TextEntry::make('accumulated_depreciation')
                            ->label('Akumulasi Depresiasi')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->accumulated_depreciation),
                        TextEntry::make('current_book_value')
                            ->label('Nilai Buku')
                            ->numeric()
                            ->state(fn (Asset $record): float => $record->current_book_value),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (AssetStatus $state): string => $state->getLabel())
                            ->color(fn (AssetStatus $state): string => $state->getColor()),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
            ]);
    }
}
