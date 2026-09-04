<?php

namespace App\Filament\Exports;

use App\Models\Category;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class CategoryExporter extends BaseExporter
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('description')
                ->label('Deskripsi'),
            ExportColumn::make('products_count')
                ->label('Jumlah Produk')
                ->counts('products'),
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
