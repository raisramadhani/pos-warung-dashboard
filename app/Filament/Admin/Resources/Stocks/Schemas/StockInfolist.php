<?php

namespace App\Filament\Admin\Resources\Stocks\Schemas;

use App\Filament\Admin\Resources\Items\ItemResource;
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
                    ->description('Detail stok gudang')
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Stok')
                            ->formatStateUsing(fn ($state) => format_quantity($state)),
                    ])
                    ->columns(1),
                Section::make('Item Terkait')
                    ->description('Barang yang terkait dengan stok ini')
                    ->schema([
                        TextEntry::make('item.name')
                            ->label('Nama Item')
                            ->url(fn ($record) => ItemResource::getUrl('view', ['record' => $record->item_id])),
                        TextEntry::make('item.type')
                            ->label('Tipe')
                            ->badge(),
                        TextEntry::make('item.unit')
                            ->label('Satuan'),
                        TextEntry::make('item.description')
                            ->label('Deskripsi')
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
