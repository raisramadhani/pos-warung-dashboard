<?php

namespace App\Filament\Exports;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\PurchaseOrder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class PurchaseOrderExporter extends BaseExporter
{
    protected static ?string $model = PurchaseOrder::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('po_number')
                ->label('No. PO'),
            ExportColumn::make('supplier.name')
                ->label('Supplier'),
            ExportColumn::make('merchant.name')
                ->label('Merchant'),
            ExportColumn::make('source_type')
                ->label('Sumber')
                ->formatStateUsing(fn (PurchaseOrderSource $state): string => $state->getLabel()),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (PurchaseOrderStatus $state): string => $state->getLabel()),
            ExportColumn::make('items_count')
                ->label('Jumlah Item')
                ->counts('items'),
            ExportColumn::make('notes')
                ->label('Catatan'),
            ExportColumn::make('approved_at')
                ->label('Disetujui')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('finished_at')
                ->label('Selesai')
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
