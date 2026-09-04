<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders\Schemas;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Purchase Order')
                    ->description('Data purchase order')
                    ->schema([
                        TextEntry::make('po_number')
                            ->label('No. PO')
                            ->copyable(),
                        TextEntry::make('source_type')
                            ->label('Sumber')
                            ->badge()
                            ->tooltip(fn (PurchaseOrderSource $state) => $state->getDescription()),
                        TextEntry::make('supplier.name')
                            ->label('Supplier')
                            ->placeholder('Supplier hilang atau dihapus')
                            ->badge()
                            ->color('secondary'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->tooltip(fn (PurchaseOrderStatus $state) => $state->getDescription()),
                        TextEntry::make('approved_at')
                            ->label('Disetujui Pada')
                            ->dateTime()
                            ->placeholder('Belum disetujui'),
                        TextEntry::make('finished_at')
                            ->label('Selesai Pada')
                            ->dateTime()
                            ->placeholder('Belum selesai'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ]),
            ]);
    }
}
