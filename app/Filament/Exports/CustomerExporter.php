<?php

namespace App\Filament\Exports;

use App\Models\Customers\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends BaseExporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('phone')
                ->label('Telepon'),
            ExportColumn::make('transactions_count')
                ->label('Jumlah Transaksi')
                ->counts('transactions'),
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
