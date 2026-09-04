<?php

namespace App\Filament\Exports;

use App\Models\Inventories\GoodsReceiptItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class GoodsReceiptItemExporter extends BaseExporter
{
    protected static ?string $model = GoodsReceiptItem::class;

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
            ExportColumn::make('quantity_ordered')
                ->label('Jumlah Dipesan'),
            ExportColumn::make('quantity_received')
                ->label('Jumlah Diterima'),
            ExportColumn::make('unit_price')
                ->label('Harga Satuan')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('subtotal')
                ->label('Subtotal')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
