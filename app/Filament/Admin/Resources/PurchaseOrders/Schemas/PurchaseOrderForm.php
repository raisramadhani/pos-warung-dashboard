<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Schemas;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\Item;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Purchase Order')
                    ->description('Data purchase order dari supplier')
                    ->schema([
                        TextInput::make('po_number')
                            ->label('No. PO')
                            ->default('Akan digenerate otomatis')
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(255),
                        Select::make('source_type')
                            ->label('Sumber')
                            ->options(PurchaseOrderSource::class)
                            ->default(PurchaseOrderSource::Purchasing->value)
                            ->required()
                            ->visible(false)
                            ->dehydrated(),
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('status')
                            ->label('Status')
                            ->options(PurchaseOrderStatus::class)
                            ->default(PurchaseOrderStatus::Approved->value)
                            ->hidden()
                            ->dehydrated(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Item Barang')
                    ->description('Daftar item yang dipesan ke supplier')
                    ->schema([
                        Repeater::make('items')
                            ->label('Item')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->collapsible()
                            ->live()
                            // ->itemLabel(fn (array $state): ?string => $state['item_id'] ? Item::find($state['item_id'])?->name : null)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'subtotal_ordered' => ($data['quantity_ordered'] ?? 0) * ($data['unit_price_ordered'] ?? 0),
                            ])
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [
                                ...$data,
                                'subtotal_ordered' => ($data['quantity_ordered'] ?? 0) * ($data['unit_price_ordered'] ?? 0),
                            ])
                            ->table([
                                TableColumn::make('Item')
                                    ->width('50%'),
                                TableColumn::make('Jumlah')
                                    ->width('150px'),
                                TableColumn::make('Harga Satuan'),
                                TableColumn::make('Subtotal'),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item')
                                    ->relationship('item', 'name')
                                    ->placeholder('Pilih item')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                TextInput::make('quantity_ordered')
                                    ->label('Jumlah')
                                    ->placeholder('Masukan jumlah')
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
                                        $quantity = to_number($get('quantity_ordered'));
                                        $unitPrice = to_number($get('unit_price_ordered'));
                                        $set('subtotal_ordered', $quantity * $unitPrice);
                                    }),
                                TextInput::make('unit_price_ordered')
                                    ->label('Harga Satuan')
                                    ->placeholder('Masukan harga satuan')
                                    ->required()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity_ordered'));
                                        $unitPrice = to_number($get('unit_price_ordered'));
                                        $set('subtotal_ordered', $quantity * $unitPrice);
                                    }),
                                TextInput::make('subtotal_ordered')
                                    ->label('Subtotal')
                                    ->placeholder('Masukan subtotal')
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->readOnly()
                                    ->dehydrated()
                                    ->afterStateHydrated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity_ordered'));
                                        $unitPrice = to_number($get('unit_price_ordered'));
                                        $set('subtotal_ordered', $quantity * $unitPrice);
                                    }),
                            ]),
                        Placeholder::make('total_amount')
                            ->label('Total')
                            ->content(fn (Get $get): string => format_rupiah(collect($get('items') ?? [])->sum(fn (array $item): int => (int) to_number($item['subtotal_ordered'] ?? 0)))),
                    ]),
            ]);
    }
}
