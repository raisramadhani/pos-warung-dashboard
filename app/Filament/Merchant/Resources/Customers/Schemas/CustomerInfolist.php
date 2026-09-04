<?php

namespace App\Filament\Merchant\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pelanggan')
                    ->description('Data utama pelanggan')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Pelanggan'),
                        TextEntry::make('phone')
                            ->label('Telepon'),
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
