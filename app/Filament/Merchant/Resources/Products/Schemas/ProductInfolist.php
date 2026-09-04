<?php

namespace App\Filament\Merchant\Resources\Products\Schemas;

use App\Models\Products\ProductMaterial;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Produk')
                    ->description('Data utama produk')
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label('Gambar')
                            ->disk('public')
                            ->square()
                            ->imageHeight(200),
                        TextEntry::make('name')
                            ->label('Nama Produk'),
                        TextEntry::make('category.name')
                            ->label('Kategori'),
                        TextEntry::make('selling_price')
                            ->label('Harga Jual')
                            ->numeric(),
                        TextEntry::make('cost_price')
                            ->label('Harga Modal')
                            ->numeric(),
                        TextEntry::make('description')
                            ->label('Deskripsi'),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Bahan Material')
                    ->description('Komposisi bahan yang dibutuhkan per produk')
                    ->schema([
                        RepeatableEntry::make('productMaterials')
                            ->label('Komposisi Bahan')
                            ->table([
                                TableColumn::make('Item'),
                                TableColumn::make('Qty Dibutuhkan'),
                            ])
                            ->schema([
                                TextEntry::make('item.name')
                                    ->label('Item'),
                                TextEntry::make('quantity_required')
                                    ->label('Qty Dibutuhkan')
                                    ->formatStateUsing(fn (float $state): string => rtrim(rtrim(number_format($state, 4, ',', '.'), '0'), ','))
                                    ->suffix(function (ProductMaterial $record) {
                                        $unit = $record->item?->unit;
                                        if ($unit) {
                                            return ' '.$unit;
                                        }

                                        return null;
                                    }),
                            ]),
                    ]),
            ]);
    }
}
