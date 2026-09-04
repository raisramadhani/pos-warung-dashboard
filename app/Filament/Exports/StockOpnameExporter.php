<?php

namespace App\Filament\Exports;

use App\Models\Inventories\StockOpname;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class StockOpnameExporter extends BaseExporter
{
    protected static ?string $model = StockOpname::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('opname_number')
                ->label('No. Opname'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('items_count')
                ->label('Jumlah Item')
                ->counts('items'),
            ExportColumn::make('total_items')
                ->label('Total Item Dihitung'),
            ExportColumn::make('total_surplus')
                ->label('Total Kelebihan'),
            ExportColumn::make('total_deficit')
                ->label('Total Kekurangan'),
            ExportColumn::make('total_difference')
                ->label('Selisih Bersih'),
            ExportColumn::make('is_lock_transactions')
                ->label('Lock Transaksi')
                ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak'),
            ExportColumn::make('notes')
                ->label('Catatan'),
            ExportColumn::make('created_at')
                ->label('Dibuat')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('completed_at')
                ->label('Selesai')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
