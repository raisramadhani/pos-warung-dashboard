<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Schemas;

use App\Models\Inventories\Item;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StockOpnameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Flex::make([
                    ToggleButtons::make('is_lock_transactions')
                        ->label('Lock Transaksi Keluar')
                        ->helperText('Cegah penerimaan barang & transaksi keluar selama stock opname berlangsung')
                        ->boolean('Ya', 'Tidak')
                        ->grouped()
                        ->default(false),
                    ToggleButtons::make('opname_all_items')
                        ->label('Stock Opname Semua Item')
                        ->helperText('Jika diaktifkan, semua item yang distok akan diikutkan')
                        ->boolean('Semua Item', 'Pilih Manual')
                        ->grouped()
                        ->default(true)
                        ->live(),
                ])->from('md'),

                Section::make('Semua Item Akan Diikutkan')
                    ->description('Stock opname akan mencakup semua item yang distok oleh merchant ini secara otomatis.')
                    ->icon('heroicon-o-information-circle')
                    ->visible(fn (Get $get): bool => $get('opname_all_items') === true),

                Repeater::make('items')
                    ->relationship('items')
                    ->hidden(fn (Get $get): bool => $get('opname_all_items') === true)
                    ->schema([
                        Select::make('item_id')
                            ->label('Item')
                            ->options(fn () => Item::query()
                                ->whereHas('merchantStocks', fn ($q) => $q->where('merchant_id', filament()->getTenant()?->getKey()))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'system_quantity' => 0,
                    ])
                    ->table([
                        TableColumn::make('Item'),
                    ])
                    ->compact()
                    ->addActionLabel('Tambah Item')
                    ->columnSpanFull(),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Masukan catatan')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }
}
