<?php

namespace App\Filament\Exports;

use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ItemExporter extends BaseExporter
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('type')
                ->label('Tipe')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('unit')
                ->label('Satuan'),
            ExportColumn::make('warehouse_stock')
                ->label('Stok Gudang')
                ->state(function (Item $record): int {
                    $warehouse = Merchant::warehouse();

                    return $warehouse
                        ? ($record->merchantStocks()->where('merchant_id', $warehouse->id)->value('quantity') ?? 0)
                        : 0;
                }),
            ExportColumn::make('description')
                ->label('Deskripsi'),
            ExportColumn::make('is_active')
                ->label('Aktif')
                ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak'),
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
