<?php

namespace App\Filament\Exports;

use App\Models\Inventories\StockOpnameItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class StockOpnameItemExporter extends BaseExporter
{
    protected static ?string $model = StockOpnameItem::class;

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
            ExportColumn::make('system_quantity')
                ->label('Stok Sistem'),
            ExportColumn::make('actual_quantity')
                ->label('Stok Fisik'),
            ExportColumn::make('difference')
                ->label('Selisih'),
            ExportColumn::make('action_type')
                ->label('Tindakan')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('notes')
                ->label('Catatan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
