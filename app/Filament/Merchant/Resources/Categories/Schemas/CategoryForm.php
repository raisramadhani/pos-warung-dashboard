<?php

namespace App\Filament\Merchant\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Kategori')
                    ->description('Data utama kategori')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->placeholder('Masukan nama kategori')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->readOnly()
                            ->dehydrated(false)
                            ->hiddenJs(<<<'JS'
                             1 == 1
                            JS),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Masukan deskripsi')
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(3),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
