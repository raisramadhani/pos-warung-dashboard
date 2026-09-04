<?php

namespace App\Filament\Merchant\Resources\StockMovements\Schemas;

use App\Models\Inventories\StockMovement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pergerakan')
                    ->description('Detail pergerakan stok')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge(),
                        TextEntry::make('quantity')
                            ->label('Jumlah')
                            ->formatStateUsing(fn ($state): string => ($state > 0 ? '+' : '').format_quantity($state))
                            ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit)
                            ->color(fn ($state): string => $state > 0 ? 'success' : 'danger'),
                        TextEntry::make('quantity_before')
                            ->label('Stok Sebelum')
                            ->formatStateUsing(fn ($state): string => format_quantity($state))
                            ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit),
                        TextEntry::make('quantity_after')
                            ->label('Stok Sesudah')
                            ->formatStateUsing(fn ($state): string => format_quantity($state))
                            ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Item')
                    ->description('Item terkait')
                    ->schema([
                        TextEntry::make('item.name')
                            ->label('Nama Item'),
                        TextEntry::make('item.unit')
                            ->label('Satuan'),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                    ]),
                Section::make('Sumber & Pelaku')
                    ->description('Dokumen sumber dan pelaku perubahan')
                    ->schema([
                        TextEntry::make('reference_type')
                            ->label('Sumber')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                        TextEntry::make('reference_id')
                            ->label('ID Referensi')
                            ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : '-'),
                        TextEntry::make('creator.name')
                            ->label('Dilakukan Oleh'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
            ]);
    }
}
