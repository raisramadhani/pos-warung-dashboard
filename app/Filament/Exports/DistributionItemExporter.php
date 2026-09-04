<?php

namespace App\Filament\Exports;

use App\Models\Inventories\DistributionItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class DistributionItemExporter extends BaseExporter
{
    protected static ?string $model = DistributionItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('item.name')
                ->label('Item'),
            ExportColumn::make('item.type')
                ->label('Tipe Item')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('item.unit')
                ->label('Satuan'),
            ExportColumn::make('quantity_sent')
                ->label('Jumlah Dikirim'),
            ExportColumn::make('quantity_received')
                ->label('Jumlah Diterima'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
