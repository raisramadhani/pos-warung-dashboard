<?php

namespace App\Filament\Merchant\Resources\CashFlows\Schemas;

use App\Enums\CashFlows\CashFlowType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Support\RawJs;

class CashFlowForm
{
    public static function fields(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    ToggleButtons::make('type')
                        ->label('Jenis')
                        ->options(CashFlowType::class)
                        ->grouped()
                        ->default(CashFlowType::Expense)
                        ->required()
                        ->live(),
                    Toggle::make('affects_cash_drawer')
                        ->label('Memengaruhi Cashdrawer')
                        ->helperText('Apabila aktif, nominal akan menambah/mengurangi saldo cashdrawer saat tutup shift')
                        ->default(true),
                ]),
            Grid::make(2)
                ->schema([
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->placeholder('Masukan jumlah')
                        ->required()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                        ->formatStateUsing(fn ($state) => format_quantity($state))
                        ->dehydrateStateUsing(fn ($state) => to_number($state)),
                    DatePicker::make('transaction_date')
                        ->label('Tanggal')
                        ->prefixIcon('heroicon-o-calendar')
                        ->required()
                        ->default(now()),
                ]),
            Textarea::make('description')
                ->label('Keterangan')
                ->placeholder('Masukan keterangan')
                ->nullable()
                ->maxLength(1000)
                ->rows(4)
                ->columnSpanFull(),
        ];
    }
}
