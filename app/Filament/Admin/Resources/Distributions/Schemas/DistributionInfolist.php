<?php

namespace App\Filament\Admin\Resources\Distributions\Schemas;

use App\Models\Inventories\DistributionItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DistributionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Distribusi')
                    ->description('Pengiriman barang ke cabang')
                    ->schema([
                        TextEntry::make('sourceMerchant.name')
                            ->label('Sumber'),
                        TextEntry::make('merchant.name')
                            ->label('Cabang'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('sent_at')
                            ->label('Dikirim Pada')
                            ->dateTime(),
                        TextEntry::make('received_at')
                            ->label('Diterima Pada')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                // Section::make('Item Barang')
                //     ->schema([
                //         RepeatableEntry::make('items')
                //             ->label('Daftar Item')
                //             ->table([
                //                 TableColumn::make('Item'),
                //                 TableColumn::make('Dikirim'),
                //                 TableColumn::make('Diterima'),
                //             ])
                //             ->schema([
                //                 TextEntry::make('item.name')
                //                     ->label('Item'),

                //                 TextEntry::make('quantity_sent')
                //                     ->label('Dikirim')
                //                     ->formatStateUsing(fn ($state) => format_quantity($state))
                //                     ->suffix(fn(?DistributionItem $record): ?string => $record?->item?->unit ? ' ' . $record->item->unit : null),

                //                 TextEntry::make('quantity_received')
                //                     ->label('Diterima')
                //                     ->formatStateUsing(fn ($state) => format_quantity($state))
                //                     ->suffix(fn(?DistributionItem $record): ?string => $record?->item?->unit ? ' ' . $record->item->unit : null),
                //             ])
                //     ]),
                // Section::make('Waktu')
                //     ->schema([
                //         TextEntry::make('created_at')
                //             ->label('Dibuat')
                //             ->dateTime(),
                //         TextEntry::make('updated_at')
                //             ->label('Diperbarui')
                //             ->dateTime(),
                //     ])
                //     ->columns([
                //         'sm' => 1,
                //         'md' => 2,
                //     ]),
            ]);
    }
}
