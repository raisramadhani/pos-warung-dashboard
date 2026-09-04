<?php

namespace App\Filament\Exports;

use App\Models\Products\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends BaseExporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('category.name')
                ->label('Kategori'),
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('selling_price')
                ->label('Harga Jual')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('cost_price')
                ->label('Harga Modal')
                ->formatStateUsing(fn ($state): string => $state === null ? '-' : format_rupiah($state)),
            ExportColumn::make('description')
                ->label('Deskripsi'),
            ExportColumn::make('is_active')
                ->label('Aktif')
                ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak'),
            ExportColumn::make('sort_order')
                ->label('Urutan'),
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
