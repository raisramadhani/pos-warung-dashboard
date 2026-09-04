<?php

namespace App\Filament\Admin\Resources\RevenueReport\Tables;

use App\Enums\Payments\PaymentMethod;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class RevenueReportTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('transaction_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('transaction_items.product_data->name', 'like', "%{$search}%")),
                TextColumn::make('item_quantity')
                    ->label('Quantity')
                    ->alignCenter()
                    ->numeric()
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->label('Total Quantity')
                    ),
                TextColumn::make('item_subtotal')
                    ->label('Pendapatan')
                    ->alignEnd()
                    ->numeric()
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->label('Total Pendapatan')
                    ),
                TextColumn::make('gross_profit')
                    ->label('Keuntungan')
                    ->alignEnd()
                    ->numeric()
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->label('Total Keuntungan')
                    ),
            ])
            ->filters([
                DateRangeFilter::make('transaction_at')
                    ->label('Periode Transaksi')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(PaymentMethod::class)
                    ->placeholder('Pilih metode bayar'),
            ]);
    }
}
