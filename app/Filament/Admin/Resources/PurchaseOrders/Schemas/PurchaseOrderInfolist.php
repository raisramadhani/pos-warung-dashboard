<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Schemas;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Admin\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Admin\Resources\Suppliers\SupplierResource;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\PurchaseOrder;
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
                            ->extraAttributes(function (PurchaseOrder $record) {
                                $extras = [
                                    'class' => 'cursor-pointer',
                                ];

                                if ($record->supplier) {
                                    $extras['x-on:click'] = 'window.open(`'.SupplierResource::getUrl('view', ['record' => $record->supplier]).'`, `_self`)';
                                }

                                return $extras;
                            })
                            ->badge()
                            ->color('secondary'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->tooltip(fn (PurchaseOrderStatus $state) => $state->getDescription())
                            ->extraAttributes(function (PurchaseOrder $record) {
                                $extras = [
                                    'class' => 'cursor-pointer',
                                ];

                                if (\in_array($record->status, [
                                    PurchaseOrderStatus::Receiving,
                                    PurchaseOrderStatus::Finished,
                                ])) {
                                    $receiptUrl = GoodsReceipt::query()
                                        ->where('purchase_order_id', $record->id)
                                        ->latest('id')
                                        ->value('id');

                                    if ($receiptUrl) {
                                        $extras['x-on:click'] = 'window.open(`'.GoodsReceiptResource::getUrl('view', ['record' => $receiptUrl]).'`, `_self`)';
                                    }
                                }

                                return $extras;
                            }),
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
