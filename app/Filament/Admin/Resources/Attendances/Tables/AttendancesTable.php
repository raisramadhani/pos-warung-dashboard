<?php

namespace App\Filament\Admin\Resources\Attendances\Tables;

use App\Enums\Attendances\PeriodType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Kehadiran Karyawan')
            ->description('Daftar periode kehadiran karyawan per outlet')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('period_type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('date_from')
                    ->label('Dari')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('date_to')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('entries_count')
                    ->label('Jumlah Entri')
                    ->counts('entries'),
                TextColumn::make('merchants_summary')
                    ->label('Outlet')
                    ->state(function ($record): string {
                        return $record->entries
                            ->pluck('merchant.name')
                            ->unique()
                            ->filter()
                            ->implode(', ');
                    })
                    ->limit(50)
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('period_type')
                    ->label('Tipe Periode')
                    ->options(PeriodType::class)
                    ->placeholder('Pilih tipe'),
                SelectFilter::make('merchant')
                    ->label('Outlet')
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('entries', fn (Builder $q) => $q->where('merchant_id', $data['value']))
                        : $query)
                    ->relationship('entries', 'merchant_id'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
