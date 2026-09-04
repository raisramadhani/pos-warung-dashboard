<?php

namespace App\Filament\Exports;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Inventories\Distribution;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class DistributionExporter extends BaseExporter
{
    protected static ?string $model = Distribution::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('sourceMerchant.name')
                ->label('Sumber'),
            ExportColumn::make('merchant.name')
                ->label('Cabang'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (DistributionStatus $state): string => $state->getLabel()),
            ExportColumn::make('items_count')
                ->label('Jumlah Item')
                ->counts('items'),
            ExportColumn::make('notes')
                ->label('Catatan'),
            ExportColumn::make('sent_at')
                ->label('Dikirim')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('received_at')
                ->label('Diterima')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
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
