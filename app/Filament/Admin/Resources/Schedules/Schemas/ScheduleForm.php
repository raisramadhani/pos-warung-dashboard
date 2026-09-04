<?php

namespace App\Filament\Admin\Resources\Schedules\Schemas;

use App\Enums\Merchants\MerchantType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;

class ScheduleForm
{
    public static function fields(): array
    {
        return [
            Select::make('user_id')
                ->label('Karyawan')
                ->prefixIcon('heroicon-o-user')
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->placeholder('Pilih karyawan'),
            Select::make('merchant_id')
                ->label('Outlet')
                ->prefixIcon('heroicon-o-building-storefront')
                ->options(fn () => Merchant::query()->where('type', MerchantType::Merchant)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->placeholder('Pilih outlet tempat bertugas'),
            Grid::make(3)
                ->schema([
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->prefixIcon('heroicon-o-calendar')
                        ->required()
                        ->displayFormat('d F Y')
                        ->closeOnDateSelection(),
                    TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->prefixIcon('heroicon-o-clock')
                        ->required()
                        ->seconds(false),
                    TimePicker::make('end_time')
                        ->label('Jam Selesai')
                        ->prefixIcon('heroicon-o-clock')
                        ->required()
                        ->seconds(false)
                        ->after('start_time'),
                ]),
            Textarea::make('notes')
                ->label('Catatan')
                ->placeholder('Masukkan catatan opsional')
                ->nullable()
                ->columnSpanFull(),
        ];
    }
}
