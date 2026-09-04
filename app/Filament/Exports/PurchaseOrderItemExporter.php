<?php

namespace App\Filament\Exports;

use App\Models\Inventories\PurchaseOrderItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class PurchaseOrderItemExporter extends BaseExporter
{
    protected static ?string $model = PurchaseOrderItem::class;

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
            ExportColumn::make('unit_price_ordered')
                ->label('Harga Satuan')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('subtotal_ordered')
                ->label('Subtotal')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
