<?php

namespace App\Filament\Merchant\Resources\Products\Schemas;

use App\Models\Inventories\Item;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Produk')
                    ->description('Data utama produk')
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Gambar Produk')
                            ->image()
                            ->directory('products')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions(['1:1'])
                            ->imagePreviewHeight('250')
                            ->saveUploadedFileUsing(function (UploadedFile $file): ?string {
                                return Image::fromUpload($file)
                                    ->cover(400, 400)
                                    ->toWebp()
                                    ->quality(80)
                                    ->storePublicly('products', 'public') ?: null;
                            })
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->placeholder('Masukan nama produk')
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
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('cost_price')
                            ->label('Harga Modal')
                            ->placeholder('Masukan harga modal')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->default(0),
                        TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->placeholder('Masukan harga jual')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn ($state) => format_quantity($state))
                            ->dehydrateStateUsing(fn ($state) => to_number($state))
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Masukan deskripsi')
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(4),
                        ToggleButtons::make('is_active')
                            ->label('Aktif')
                            ->boolean()
                            ->grouped()
                            ->default(true),

                    ]),
                Section::make('Bahan Material')
                    ->description('Komposisi bahan yang dibutuhkan per produk. Akan otomatis mengurangi stok bahan baku saat produk dijual.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('productMaterials')
                            ->label('Bahan Material')
                            ->relationship()
                            ->table([
                                TableColumn::make('Item'),
                                TableColumn::make('Qty Dibutuhkan'),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                TextInput::make('quantity_required')
                                    ->label('Qty Dibutuhkan')
                                    ->placeholder('Masukan qty dibutuhkan (mis. 0.0005)')
                                    ->default(1)
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                                    ->formatStateUsing(fn ($state) => format_quantity($state))
                                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                                    ->suffix(function (Get $get): ?string {
                                        $itemId = $get('item_id');

                                        if (! $itemId) {
                                            return null;
                                        }

                                        return Item::find($itemId)?->unit;
                                    }),
                            ]),
                    ]),
            ]);
    }
}
