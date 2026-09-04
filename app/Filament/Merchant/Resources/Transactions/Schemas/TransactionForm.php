<?php

namespace App\Filament\Merchant\Resources\Transactions\Schemas;

use App\Enums\Payments\PaymentMethod;
use App\Models\Products\Product;
use Filament\Forms\Components\DateTimePicker;
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

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Detail Transaksi')
                    ->description('Pilih metode pembayaran dan tanggal transaksi')
                    ->columns(2)
                    ->schema([
                        Select::make('payment_method')
                            ->label('Metode Bayar')
                            ->options(PaymentMethod::class)
                            ->required(),

                        DateTimePicker::make('transaction_at')
                            ->label('Tanggal Transaksi')
                            ->default(now())
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Produk')
                    ->description('Produk yang dibeli dalam transaksi ini')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('transactionItems')
                            ->label('Produk')
                            ->relationship()
                            ->collapsible()
                            ->columns(3)
                            ->minItems(1)
                            ->table([
                                TableColumn::make('Produk'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Harga Satuan'),
                                TableColumn::make('Subtotal'),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        if ($state) {
                                            $product = Product::query()->find($state);
                                            if ($product) {
                                                $set('unit_price', $product->selling_price);
                                            }
                                        }
                                    }),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->placeholder('Masukan qty')
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $quantity = to_number($get('quantity'));
                                        $unitPrice = to_number($get('unit_price'));
                                        $set('subtotal', $quantity * $unitPrice);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->placeholder('Masukan harga satuan')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->readOnly(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->placeholder('Masukan subtotal')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->readOnly(),
                            ]),
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->placeholder('Masukan subtotal')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->default(0)
                            ->readOnly()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $subtotal = to_number($get('subtotal'));
                                $set('total_amount', max(0, $subtotal));
                            }),
                        TextInput::make('total_amount')
                            ->label('Grandtotal')
                            ->placeholder('Masukan grandtotal')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->default(0)
                            ->readOnly()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $total = to_number($get('total_amount'));
                                $received = to_number($get('amount_received'));
                                if ($received > 0) {
                                    $set('change', max(0, $received - $total));
                                }
                            }),
                        TextInput::make('amount_received')
                            ->label('Uang Diterima')
                            ->placeholder('Masukan uang diterima')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $total = to_number($get('total_amount'));
                                $received = to_number($get('amount_received'));
                                $set('change', max(0, $received - $total));
                            }),
                        TextInput::make('change')
                            ->label('Kembalian')
                            ->placeholder('Kembalian')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->default(0)
                            ->readOnly(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan')
                            ->nullable()
                            ->maxLength(500)
                            ->rows(3),
                    ]),
            ]);
    }
}
