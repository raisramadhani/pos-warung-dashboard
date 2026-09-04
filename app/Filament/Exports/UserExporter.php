<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends BaseExporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('username')
                ->label('Username'),
            ExportColumn::make('role')
                ->label('Role')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('merchants_count')
                ->label('Jumlah Merchant')
                ->counts('merchants'),
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
