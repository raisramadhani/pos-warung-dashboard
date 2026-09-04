<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Exports\ProductExporter;
use App\Models\Products\Product;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Gambar')
                    ->disk('public')
                    ->defaultImageUrl(fn (Product $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name))
                    ->square(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-tag'),

                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('cost_price')
                    ->label('Modal')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('product_materials_count')
                    ->label('Bahan')
                    ->counts('productMaterials')
                    ->icon('heroicon-o-cube')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->tooltip('Jumlah bahan yang digunakan dalam produk ini')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProductExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(ProductExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
