<?php

namespace App\Filament\Admin\Resources\Items\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Item')
                    ->description('Data master barang')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Item'),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge(),
                        TextEntry::make('unit')
                            ->label('Satuan'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),
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
