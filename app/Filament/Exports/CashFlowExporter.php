<?php

namespace App\Filament\Exports;

use App\Models\CashFlows\CashFlow;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class CashFlowExporter extends BaseExporter
{
    protected static ?string $model = CashFlow::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('merchant.name')
                ->label('Mitra')
                ->formatStateUsing(fn ($state): string => $state ?? '-'),
            ExportColumn::make('type')
                ->label('Jenis')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? '-'),
            ExportColumn::make('description')
                ->label('Keterangan'),
            ExportColumn::make('amount')
                ->label('Jumlah')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('transaction_date')
                ->label('Tanggal')
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
