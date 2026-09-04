<?php

namespace App\Filament\Merchant\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Supplier')
                    ->description('Data utama supplier')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Supplier')
                            ->placeholder('Masukan nama supplier')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->readOnly()
                            ->dehydrated(false)
                            ->hiddenJs(<<<'JS'
                             1 == 1
                            JS),
                        TextInput::make('contact_person')
                            ->label('Kontak Person')
                            ->placeholder('Masukan nama kontak')
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('Masukan alamat email')
                            ->nullable()
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->placeholder('Masukan nomor telepon')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Alamat & Status')
                    ->description('Lokasi dan status supplier')
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat')
                            ->placeholder('Masukan alamat')
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
