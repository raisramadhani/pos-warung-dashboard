<?php

namespace App\Filament\Admin\Resources\Payrolls\Tables;

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Actions\ApprovePayrollAction;
use App\Filament\Admin\Resources\Payrolls\Actions\CancelPayrollAction;
use App\Filament\Admin\Resources\Payrolls\Actions\ExportPayslipAction;
use App\Filament\Admin\Resources\Payrolls\BulkActions\ExportPayslipBulkAction;
use App\Filament\Exports\PayrollExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
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
                TextColumn::make('period_start')
                    ->label('Periode Mulai')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('period_end')
                    ->label('Periode Akhir')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Komponen')
                    ->alignCenter()
                    ->counts('items'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // SelectFilter::make('status')
                //     ->label('Status')
                //     ->options(PayrollStatus::class)
                //     ->placeholder('Pilih status'),
                DateRangeFilter::make('period_start')
                    ->label('Periode')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name')
                    ->placeholder('Pilih outlet'),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    ExportPayslipAction::make(),
                    ApprovePayrollAction::make(),
                    CancelPayrollAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => ! \in_array($record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])),
                    DeleteAction::make()
                        ->visible(fn ($record) => ! \in_array($record->status, [PayrollStatus::Paid, PayrollStatus::Canceled])),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PayrollExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportPayslipBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(PayrollExporter::class)
                        ->columnMapping(false),
                ]),
            ]);
    }
}
