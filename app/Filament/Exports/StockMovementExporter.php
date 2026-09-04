<?php

namespace App\Filament\Exports;

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\StockMovement;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class StockMovementExporter extends BaseExporter
{
    protected static ?string $model = StockMovement::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('created_at')
                ->label('Waktu')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('merchant.name')
                ->label('Merchant'),
            ExportColumn::make('item.name')
                ->label('Item'),
            ExportColumn::make('type')
                ->label('Tipe')
                ->formatStateUsing(fn (StockMovementType $state): string => $state->getLabel()),
            ExportColumn::make('quantity')
                ->label('Jumlah')
                ->formatStateUsing(fn ($state): string => $state > 0 ? "+{$state}" : (string) $state),
            ExportColumn::make('quantity_before')
                ->label('Sebelum'),
            ExportColumn::make('quantity_after')
                ->label('Sesudah'),
            ExportColumn::make('reference_type')
                ->label('Sumber')
                ->formatStateUsing(fn ($state): string => $state ? class_basename($state) : '-'),
            ExportColumn::make('creator.name')
                ->label('Oleh'),
            ExportColumn::make('notes')
                ->label('Catatan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
