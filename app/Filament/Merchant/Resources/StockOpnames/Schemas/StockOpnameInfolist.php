<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockOpnameInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Stock Opname')
                    ->schema([
                        TextEntry::make('opname_number')
                            ->label('No. Opname')
                            ->columnSpan(1),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->columnSpan(1),
                        IconEntry::make('is_lock_transactions')
                            ->label('Lock Transaksi')
                            ->boolean()
                            ->columnSpan(1),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime()
                            ->columnSpan(1),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh')
                            ->columnSpan(1),
                        TextEntry::make('started_at')
                            ->label('Mulai Hitung')
                            ->dateTime()
                            ->placeholder('-')
                            ->columnSpan(1),
                        TextEntry::make('completed_at')
                            ->label('Selesai')
                            ->dateTime()
                            ->placeholder('-')
                            ->columnSpan(1),
                        TextEntry::make('canceled_at')
                            ->label('Dibatalkan')
                            ->dateTime()
                            ->placeholder('-')
                            ->columnSpan(1),
                        TextEntry::make('total_items')
                            ->label('Item Dihitung')
                            ->columnSpan(1),
                        TextEntry::make('total_surplus')
                            ->label('Total Kelebihan')
                            ->color('warning')
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->columnSpan(1),
                        TextEntry::make('total_deficit')
                            ->label('Total Kekurangan')
                            ->color('danger')
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->columnSpan(1),
                        TextEntry::make('total_difference')
                            ->label('Selisih Bersih')
                            ->color(fn (mixed $state): string => match (true) {
                                (int) ($state ?? 0) < 0 => 'danger',
                                (int) ($state ?? 0) > 0 => 'warning',
                                default => 'success',
                            })
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->columnSpan(1),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ]),
            ]);
    }
}
