<?php

namespace App\Filament\Merchant\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pelanggan')
                    ->description('Data utama pelanggan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Pelanggan')
                            ->placeholder('Masukan nama pelanggan')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->placeholder('Masukan nomor telepon')
                            ->nullable()
                            ->maxLength(255)
                            ->tel(),
                    ])
                    ->columns(2),
            ]);
    }
}
