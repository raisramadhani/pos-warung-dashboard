<?php

namespace App\Filament\Admin\Resources\Items\Schemas;

use App\Enums\Inventories\ItemType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Item')
                    ->description('Data master barang')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Item')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Gula Pasir, Minyak Goreng, Kertas Struk, dll.'),
                                Select::make('unit')
                                    ->label('Satuan')
                                    ->live()
                                    ->hintIcon(fn ($get, $record): string => $record && $record->unit && $record->unit !== $get('unit')
                                        ? 'heroicon-m-exclamation-triangle'
                                        : 'heroicon-m-question-mark-circle')
                                    ->hintColor(fn ($get, $record): ?string => $record && $record->unit && $record->unit !== $get('unit')
                                        ? 'danger'
                                        : null)
                                    ->hintIconTooltip(fn ($get, $record): ?string => $record && $record->unit && $record->unit !== $get('unit')
                                        ? null
                                        : "Pilih satuan terkecil. Contoh: Gunakan 'Gram' (bukan 'Kg') untuk Gula, agar takaran resep per porsi bisa diisi 20 Gram.")
                                    ->options([
                                        'box' => 'Box',
                                        'gram' => 'Gram',
                                        'kg' => 'Kg',
                                        'liter' => 'Liter',
                                        'meter' => 'Meter',
                                        'ml' => 'Ml',
                                        'pack' => 'Pack',
                                        'pcs' => 'Pcs',
                                        'unit' => 'Unit',
                                    ])
                                    ->helperText(fn ($get, $record): string => $record && $record->unit && $record->unit !== $get('unit')
                                        ? 'Perhatian! Satuan item ini digunakan di Komposisi Bahan Baku per Produk. Mengubah satuan tanpa menyesuaikan quantity di komposisi dapat mengacaukan perhitungan biaya bahan baku.'
                                        : 'Silahkan isi satuan terkecil karena digunakan untuk bahan baku per produk.')
                                    ->required()
                                    ->searchable(),
                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Contoh: Bahan baku untuk membuat kue, atau bahan baku untuk membuat minuman, atau alat untuk mencetak kue, dll.')
                                    ->nullable()
                                    ->rows(3),
                                Flex::make([
                                    ToggleButtons::make('type')
                                        ->label('Tipe')
                                        ->inline()
                                        ->grouped()
                                        ->options(ItemType::class)
                                        ->required()
                                        ->live(),
                                    ToggleButtons::make('is_active')
                                        ->label('Status')
                                        ->boolean('Aktif', 'Nonaktif')
                                        ->grouped()
                                        ->default(true),
                                ])
                                    ->from('md'),
                                TextInput::make('opening_stock')
                                    ->label('Stok Awal (Opsional)')
                                    ->placeholder('Masukan jumlah stok awal')
                                    ->minValue(0)
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->suffix(fn (Get $get): ?string => filled($get('unit')) ? $get('unit') : null)
                                    ->visible(fn (string $operation): bool => $operation === 'create')
                                    ->helperText('Isi stok awal HANYA saat pertama kali dibuat (mis. migrasi data lama), khusus bahan baku. Stok dicatat ke gudang utama. JANGAN isi jika stok akan masuk via PO / Penerimaan Barang / Distribusi, agar tidak terjadi stok ganda.'),
                            ]),
                    ]),
            ]);
    }
}
