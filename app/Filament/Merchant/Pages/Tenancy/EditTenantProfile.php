<?php

namespace App\Filament\Merchant\Pages\Tenancy;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditTenantProfile extends \Filament\Pages\Tenancy\EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Profil Toko';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil toko berhasil diperbarui';
    }

    protected function getRedirectUrl(): ?string
    {
        return route('home');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Toko')
                    ->description('Perbarui informasi profil toko Anda.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Logo Toko')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('merchant-avatars')
                            ->visibility('public')
                            ->nullable()
                            ->helperText('Format gambar (JPG/PNG/WebP). Maksimal 1 file.')
                            ->alignCenter(),
                        TextInput::make('name')
                            ->label('Nama Toko')
                            ->required()
                            ->placeholder('Masukan nama toko Anda')
                            ->maxLength(255),
                        // TextInput::make('latitude')
                        //     ->label('Latitude')
                        //     ->numeric()
                        //     ->step(0.0000001)
                        //     ->placeholder('-6.200000'),
                        // TextInput::make('longitude')
                        //     ->label('Longitude')
                        //     ->numeric()
                        //     ->step(0.0000001)
                        //     ->placeholder('106.816666'),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->placeholder('Masukan alamat toko Anda')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
