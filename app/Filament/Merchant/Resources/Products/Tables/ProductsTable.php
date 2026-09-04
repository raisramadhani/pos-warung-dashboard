<?php

namespace App\Filament\Merchant\Resources\Products\Tables;

use App\Filament\Exports\ProductExporter;
use App\Models\Products\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
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
            ->reorderable('sort_order')
            // ->contentGrid(...) dihapus agar kembali ke tampilan tabel baris normal
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Gambar')
                    ->disk('public')
                    ->defaultImageUrl(fn (Product $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name))
                    ->square(), // Menggunakan bentuk kotak standar tabel

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

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
                    // ->badge()
                    // ->color('info')
                    // ->tooltip('Harga jual produk ini')
                    // ->icon('tabler-currency-dollar')
                    ->alignEnd(), // Mengganti extraAttributes class md:text-end dengan bawaan Filament

                TextColumn::make('cost_price')
                    ->label('Modal')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    // ->badge()
                    // ->color('warning')
                    // ->tooltip('Modal produk ini')
                    // ->icon('tabler-currency-dollar')
                    ->alignEnd(), // Meratakan text ke kanan

                TextColumn::make('product_materials_count')
                    ->label('Bahan')
                    ->counts('productMaterials')
                    ->icon('heroicon-o-cube')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    // ->color('secondary')
                    ->tooltip('Jumlah bahan yang digunakan dalam produk ini')
                    ->alignEnd(), // Meratakan text ke kanan
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->placeholder('Pilih kategori'),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProductExporter::class)
                    ->columnMapping(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ProductExporter::class)
                        ->columnMapping(false),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
