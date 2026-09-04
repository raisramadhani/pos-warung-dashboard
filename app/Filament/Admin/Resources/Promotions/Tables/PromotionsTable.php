<?php

namespace App\Filament\Admin\Resources\Promotions\Tables;

use App\Enums\Promotions\PromotionType;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Promo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('schedules_count')
                    ->label('Jadwal')
                    ->counts('schedules')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('redemptions_count')
                    ->label('Pemakaian')
                    ->counts('redemptions')
                    ->numeric()
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(PromotionType::class),
                TernaryFilter::make('is_active'),
            ]);
    }
}
