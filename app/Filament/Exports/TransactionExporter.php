<?php

namespace App\Filament\Exports;

use App\Enums\Payments\PaymentMethod;
use App\Models\Transactions\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends BaseExporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('transaction_number')
                ->label('No. Transaksi'),
            ExportColumn::make('transaction_at')
                ->label('Waktu')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y H:i') ?? '-'),
            ExportColumn::make('customer.name')
                ->label('Pelanggan'),
            ExportColumn::make('payment_method')
                ->label('Metode Bayar')
                ->formatStateUsing(fn (PaymentMethod $state): string => $state->getLabel()),
            ExportColumn::make('subtotal')
                ->label('Subtotal')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('discount')
                ->label('Diskon')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('total_amount')
                ->label('Total')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('amount_received')
                ->label('Uang Diterima')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('change')
                ->label('Kembalian')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('items_count')
                ->label('Jumlah Item'),
            ExportColumn::make('notes')
                ->label('Catatan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export selesai. '.number_format($export->successful_rows).' baris berhasil diekspor.';
    }
}
