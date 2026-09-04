<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Schemas;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        // Resolve id gudang default (merchant pertama bertipe warehouse) sekali
        // per request, lalu di-capture ke closure agar tidak di-query ulang.
        $warehouseId = Merchant::query()
            ->where('type', 'warehouse')
            ->first()?->id;

        return $schema
            ->schema([
                Section::make('Informasi Penerimaan')
                    ->description('Data penerimaan barang')
                    ->schema([
                        TextInput::make('receipt_number')
                            ->label('No. Penerimaan')
                            ->placeholder('Masukan nomor penerimaan')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Kosongkan untuk generate otomatis'),
                        Select::make('source_type')
                            ->label('Sumber')
                            ->options(ReceiptSourceType::class)
                            ->default(ReceiptSourceType::Purchasing->value)
                            ->required()
                            ->live(),
                        Select::make('merchant_id')
                            ->label('Tujuan Masuk')
                            ->relationship('merchant', 'name')
                            ->searchable()
                            ->preload()
                            ->default($warehouseId)
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(GoodsReceiptStatus::class)
                            ->default(GoodsReceiptStatus::Draft->value)
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Item Barang')
                    ->description('Daftar item yang diterima')
                    ->schema([
                        Repeater::make('items')
                            ->label('Item')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->collapsible()
                            // ->itemLabel(fn (array $state): ?string => $state['item_id'] ? Item::find($state['item_id'])?->name : null)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'subtotal' => ($data['quantity_received'] ?? 0) * ($data['unit_price'] ?? 0),
                            ])
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                                if (! $record instanceof GoodsReceiptItem) {
                                    return $data;
                                }

                                $quantity = $data['quantity_received'] ?? $record->quantity_received;
                                $unitPrice = $data['unit_price'] ?? $record->unit_price;

                                return [
                                    ...$data,
                                    'subtotal' => $quantity * $unitPrice,
                                ];
                            })
                            ->table([
                                TableColumn::make('Item'),
                                TableColumn::make('Dipesan'),
                                TableColumn::make('Jumlah Diterima'),
                                TableColumn::make('Harga Satuan'),
                                TableColumn::make('Subtotal'),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                TextInput::make('quantity_ordered')
                                    ->label('Dipesan')
                                    ->placeholder('Masukan jumlah dipesan')
                                    ->default(0)
                                    ->minValue(0)
                                    ->readOnly()
                                    ->visible(fn (Get $get): bool => $get('../../source_type') === ReceiptSourceType::Purchasing->value)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state)),
                                TextInput::make('quantity_received')
                                    ->label('Jumlah Diterima')
                                    ->placeholder('Masukan jumlah diterima')
                                    ->required()

                                    ->default(1)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->live(onBlur: true)
                                    ->suffix(function (Get $get): ?string {
                                        $itemId = $get('item_id');

                                        if (! $itemId) {
                                            return null;
                                        }

                                        return Item::find($itemId)?->unit;
                                    })
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity_received'));
                                        $unitPrice = to_number($get('unit_price'));
                                        $set('subtotal', $quantity * $unitPrice);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->placeholder('Masukan harga satuan')
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity_received'));
                                        $unitPrice = to_number($get('unit_price'));
                                        $set('subtotal', $quantity * $unitPrice);
                                    }),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->placeholder('Masukan subtotal')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->afterStateHydrated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity_received'));
                                        $unitPrice = to_number($get('unit_price'));
                                        $set('subtotal', $quantity * $unitPrice);
                                    }),
                            ]),
                    ]),
            ]);
    }
}
