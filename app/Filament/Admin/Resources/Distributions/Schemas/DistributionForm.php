<?php

namespace App\Filament\Admin\Resources\Distributions\Schemas;

use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class DistributionForm
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
                Section::make('Informasi Distribusi')
                    ->description('Pengiriman barang ke cabang')
                    ->schema([
                        Select::make('source_merchant_id')
                            ->label('Asal')
                            ->relationship('sourceMerchant', 'name')
                            ->searchable()
                            ->preload()
                            ->default($warehouseId)
                            ->required(),
                        Select::make('merchant_id')
                            ->label('Tujuan')
                            ->relationship('merchant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Item Barang')
                    ->schema([
                        Repeater::make('items')
                            ->label('Daftar Item')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->collapsible()
                            // ->itemLabel(fn (array $state): ?string => $state['item_id'] ? Item::find($state['item_id'])?->name : null)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'quantity_received' => 0,
                            ])
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [
                                ...$data,
                                'quantity_received' => 0,
                            ])
                            ->table([
                                TableColumn::make('Item'),
                                TableColumn::make('Jumlah Dikirim'),
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
                                TextInput::make('quantity_sent')
                                    ->label('Jumlah Dikirim')
                                    ->placeholder('Masukan jumlah dikirim')
                                    ->required()
                                    ->default(1)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->suffix(function (Get $get): ?string {
                                        $itemId = $get('item_id');

                                        if (! $itemId) {
                                            return null;
                                        }

                                        return Item::find($itemId)?->unit;
                                    }),
                            ]),
                    ]),
            ]);
    }
}
