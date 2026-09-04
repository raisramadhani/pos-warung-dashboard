<?php

namespace App\Filament\Exports;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Payrolls\Payroll;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class PayrollExporter extends BaseExporter
{
    protected static ?string $model = Payroll::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('user.name')
                ->label('Karyawan'),
            ExportColumn::make('merchant.name')
                ->label('Outlet'),
            ExportColumn::make('period_start')
                ->label('Periode Mulai')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y') ?? '-'),
            ExportColumn::make('period_end')
                ->label('Periode Akhir')
                ->formatStateUsing(fn ($state): string => $state?->format('d M Y') ?? '-'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (PayrollStatus $state): string => $state->getLabel()),
            ExportColumn::make('items_count')
                ->label('Komponen')
                ->counts('items'),
            ExportColumn::make('total_amount')
                ->label('Total')
                ->formatStateUsing(fn ($state): string => format_rupiah($state)),
            ExportColumn::make('notes')
                ->label('Catatan'),
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
