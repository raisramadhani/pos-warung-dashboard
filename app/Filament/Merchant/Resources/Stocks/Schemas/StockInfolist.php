<?php

namespace App\Filament\Merchant\Resources\Stocks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Stok')
                    ->description('Detail stok di outlet')
                    ->schema([
                        TextEntry::make('item.name')
                            ->label('Item'),
                        TextEntry::make('item.type')
                            ->label('Tipe')
                            ->badge(),
                        TextEntry::make('item.unit')
                            ->label('Satuan'),
                        TextEntry::make('quantity')
                            ->label('Stok')
                            ->formatStateUsing(fn ($state) => format_quantity($state)),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Merchant')
                    ->description('Outlet')
                    ->schema([
                        TextEntry::make('merchant.name')
                            ->label('Nama Merchant'),
                        TextEntry::make('merchant.current_status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('merchant.address')
                            ->label('Alamat'),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
            ]);
    }
}
