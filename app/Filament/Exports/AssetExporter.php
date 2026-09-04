<?php

namespace App\Filament\Exports;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class AssetExporter extends BaseExporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama Aset'),
            ExportColumn::make('item.name')
                ->label('Item'),
            ExportColumn::make('description')
                ->label('Deskripsi'),
            ExportColumn::make('acquisition_date')
                ->label('Tgl Akuisisi')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y') ?? '-'),
            ExportColumn::make('acquisition_cost')
                ->label('Harga Akuisisi')
                ->formatStateUsing(fn ($state): string => $state === null ? '-' : format_rupiah($state)),
            ExportColumn::make('depreciation_method')
                ->label('Metode Penyusutan')
                ->formatStateUsing(fn (DepreciationMethod $state): string => $state->getLabel()),
            ExportColumn::make('useful_life_months')
                ->label('Umur (bln)'),
            ExportColumn::make('monthly_depreciation')
                ->label('Depresiasi/Bln')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('accumulated_depreciation')
                ->label('Akumulasi Depresiasi')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('current_book_value')
                ->label('Nilai Buku')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (AssetStatus $state): string => $state->getLabel()),
            ExportColumn::make('last_depreciation_date')
                ->label('Depresiasi Terakhir')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y') ?? '-'),
            ExportColumn::make('created_at')
                ->label('Dibuat')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
