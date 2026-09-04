<?php

namespace App\Filament\Merchant\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Supplier')
                    ->description('Data utama supplier')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Supplier'),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('contact_person')
                            ->label('Kontak Person'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('phone')
                            ->label('Telepon'),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Alamat & Status')
                    ->description('Lokasi dan status supplier')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Alamat'),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
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
