<?php

namespace App\Filament\Merchant\Resources\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Kategori')
                    ->description('Data utama kategori')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Kategori'),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('description')
                            ->label('Deskripsi'),
                        TextEntry::make('products_count')
                            ->label('Jumlah Produk'),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Merchant')
                    ->description('Outlet yang memiliki kategori ini')
                    ->schema([
                        TextEntry::make('merchant.name')
                            ->label('Nama Merchant'),
                        TextEntry::make('merchant.current_status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('merchant.address')
                            ->label('Alamat'),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                    ]),
            ]);
    }
}
