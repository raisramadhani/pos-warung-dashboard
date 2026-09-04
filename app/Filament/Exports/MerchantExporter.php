<?php

namespace App\Filament\Exports;

use App\Models\Merchants\Merchant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class MerchantExporter extends BaseExporter
{
    protected static ?string $model = Merchant::class;

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
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('current_status')
                ->label('Status')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('ownership_type')
                ->label('Kepemilikan')
                ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? $state),
            ExportColumn::make('members_count')
                ->label('Jumlah Anggota')
                ->counts('members'),
            ExportColumn::make('address')
                ->label('Alamat'),
            ExportColumn::make('latitude')
                ->label('Latitude'),
            ExportColumn::make('longitude')
                ->label('Longitude'),
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
