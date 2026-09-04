<?php

namespace App\Filament\Exports;

use App\Models\Inventories\AssetDepreciation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class AssetDepreciationExporter extends BaseExporter
{
    protected static ?string $model = AssetDepreciation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('asset.name')
                ->label('Aset'),
            ExportColumn::make('period_date')
                ->label('Periode')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y') ?? '-'),
            ExportColumn::make('depreciation_amount')
                ->label('Depresiasi')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('book_value_before')
                ->label('Nilai Buku Awal')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('book_value_after')
                ->label('Nilai Buku Akhir')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
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
