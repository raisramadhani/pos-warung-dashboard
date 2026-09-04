<?php

namespace App\Filament\Admin\Resources\Merchants\Schemas;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Informasi Outlet')
                            ->description('Data utama outlet')
                            ->schema([
                                FileUpload::make('avatar_path')
                                    ->label('Foto')
                                    ->image()
                                    ->disk('public')
                                    ->directory('merchant-avatars')
                                    ->visibility('public')
                                    ->nullable(),
                                TextInput::make('name')
                                    ->label('Nama Outlet')
                                    ->placeholder('Masukan nama outlet')
                                    ->required()
                                    ->maxLength(255),
                                Hidden::make('type')
                                    ->default(MerchantType::Merchant->value)
                                    ->required(),
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->hiddenJs(<<<'JS'
                                     1 == 1
                                    JS),
                            ]),
                        Section::make('Lokasi & Status')
                            ->description('Lokasi dan status outlet')
                            ->schema([
                                Textarea::make('address')
                                    ->label('Alamat')
                                    ->placeholder('Masukan alamat')
                                    ->nullable(),
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->placeholder('Masukan latitude')
                                    ->nullable()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->hidden(),
                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->placeholder('Masukan longitude')
                                    ->nullable()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->hidden(),
                                Grid::make(2)
                                    ->schema([
                                        Select::make('ownership_type')
                                            ->label('Tipe Kepemilikan')
                                            ->options(OwnershipType::class)
                                            ->default(OwnershipType::Main->value)
                                            ->required(),
                                        Select::make('current_status')
                                            ->label('Status')
                                            ->options(MerchantStatus::class)
                                            ->default(MerchantStatus::Inactive->value),
                                    ]),
                            ]),
                    ]),
                Section::make('Akun Pengguna')
                    ->description('Akun user yang akan login ke panel outlet untuk outlet ini')
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama User')
                            ->placeholder('Masukan nama user')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('user.username')
                            ->label('Username')
                            ->placeholder('Masukan username')
                            ->required()
                            ->unique(table: 'users', column: 'username', ignoreRecord: true)
                            ->regex('/^[a-z0-9_]+$/')
                            ->maxLength(255),
                        TextInput::make('user.email')
                            ->label('Email')
                            ->placeholder('Masukan alamat email')
                            ->email()
                            ->nullable()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('user.phone')
                            ->label('Telepon')
                            ->placeholder('Masukan nomor telepon')
                            ->tel()
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('user.password')
                            ->label('Password')
                            ->placeholder('Masukan password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                    ])
                    ->columns(2),
            ]);
    }
}
