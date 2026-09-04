<?php

namespace App\Filament\Admin\Resources\Merchants\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Merchant')
                    ->description('Data utama merchant')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Merchant'),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge(),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('current_status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('ownership_type')
                            ->label('Kepemilikan')
                            ->badge(),
                        ImageEntry::make('avatar_path')
                            ->label('Foto')
                            ->disk('public')
                            ->visibility('public')
                            ->hidden(fn (?string $state): bool => blank($state)),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Lokasi')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Alamat'),
                        TextEntry::make('latitude')
                            ->label('Latitude')
                            ->hidden(),
                        TextEntry::make('longitude')
                            ->label('Longitude')
                            ->hidden(),
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
