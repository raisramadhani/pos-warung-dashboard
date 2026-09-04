<?php

namespace App\Filament\Admin\Resources\Schedules\Tables;

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Schedules\Actions\EditScheduleAction;
use App\Filament\Admin\Resources\Schedules\Actions\ViewScheduleAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Jam Mulai'),
                TextColumn::make('end_time')
                    ->label('Jam Selesai'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih karyawan'),
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->where('type', MerchantType::Merchant))
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                DateRangeFilter::make('date')
                    ->label('Tanggal')
                    ->placeholder('Pilih rentang tanggal')
                    ->defaultThisMonth(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewScheduleAction::make(),
                    EditScheduleAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
