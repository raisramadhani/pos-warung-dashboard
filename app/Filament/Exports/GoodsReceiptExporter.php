<?php

namespace App\Filament\Exports;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Models\Inventories\GoodsReceipt;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class GoodsReceiptExporter extends BaseExporter
{
    protected static ?string $model = GoodsReceipt::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('receipt_number')
                ->label('No. Penerimaan'),
            ExportColumn::make('purchaseOrder.po_number')
                ->label('No. PO'),
            ExportColumn::make('merchant.name')
                ->label('Merchant'),
            ExportColumn::make('source_type')
                ->label('Sumber')
                ->formatStateUsing(fn (ReceiptSourceType $state): string => $state->getLabel()),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (GoodsReceiptStatus $state): string => $state->getLabel()),
            ExportColumn::make('items_count')
                ->label('Jumlah Item')
                ->counts('items'),
            ExportColumn::make('notes')
                ->label('Catatan'),
            ExportColumn::make('verified_at')
                ->label('Diverifikasi')
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
