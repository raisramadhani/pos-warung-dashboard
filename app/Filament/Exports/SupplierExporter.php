<?php

namespace App\Filament\Exports;

use App\Models\Supplier;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class SupplierExporter extends BaseExporter
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('merchant.name')
                ->label('Merchant'),
            ExportColumn::make('contact_person')
                ->label('Kontak'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Telepon'),
            ExportColumn::make('address')
                ->label('Alamat'),
            ExportColumn::make('is_active')
                ->label('Aktif')
                ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak'),
            ExportColumn::make('purchase_orders_count')
                ->label('Jumlah PO')
                ->counts('purchaseOrders'),
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
