<?php

namespace App\Filament\Exports;

use App\Models\Inventories\MerchantStock;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class MerchantStockExporter extends BaseExporter
{
    protected static ?string $model = MerchantStock::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('merchant.name')
                ->label('Merchant'),
            ExportColumn::make('item.name')
                ->label('Item'),
            ExportColumn::make('item.type')
                ->label('Tipe Item')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('item.unit')
                ->label('Satuan'),
            ExportColumn::make('quantity')
                ->label('Jumlah'),
            ExportColumn::make('created_at')
                ->label('Dibuat')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('updated_at')
                ->label('Diperbarui')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
