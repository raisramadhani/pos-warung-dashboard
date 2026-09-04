<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\RoleType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Akun')
                    ->description('Data utama pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->placeholder('Masukan nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Username')
                            ->placeholder('Masukan username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9_]+$/')
                            ->maxLength(255),
                        Select::make('role')
                            ->label('Role')
                            ->options(RoleType::class)
                            ->default(RoleType::Merchant)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Detail Lainnya')
                    ->description('Informasi tambahan')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->placeholder('Masukan password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->hiddenOn('edit'),
                    ])
                    ->columns(2),
            ]);
    }
}
