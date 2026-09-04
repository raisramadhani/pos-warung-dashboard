<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GoodsReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Penerimaan')
                    ->description('Data penerimaan barang')
                    ->schema([
                        TextEntry::make('receipt_number')
                            ->label('No. Penerimaan'),
                        TextEntry::make('purchaseOrder.po_number')
                            ->label('No. PO')
                            ->placeholder('-'),
                        TextEntry::make('source_type')
                            ->label('Sumber')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('verified_at')
                            ->label('Diverifikasi Pada')
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
            ]);
    }
}
