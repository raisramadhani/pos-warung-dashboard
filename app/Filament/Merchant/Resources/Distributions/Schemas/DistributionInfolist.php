<?php

namespace App\Filament\Merchant\Resources\Distributions\Schemas;

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
                    ->description('Pengiriman barang ke outlet')
                    ->schema([
                        TextEntry::make('sourceMerchant.name')
                            ->label('Sumber')
                            ->placeholder('-'),
                        TextEntry::make('merchant.name')
                            ->label('Outlet')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('sent_at')
                            ->label('Dikirim Pada')
                            ->placeholder('-')
                            ->dateTime(),
                        TextEntry::make('received_at')
                            ->label('Diterima Pada')
                            ->placeholder('-')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-')
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
