<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Schemas;

use App\Models\CashDrawer\CashDrawerShift;
use App\Services\CashDrawerService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CashDrawerShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Shift')
                    ->schema([
                        TextEntry::make('shift_number')
                            ->label('No. Shift'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('opening_amount')
                            ->label('Saldo Awal')
                            ->numeric()
                            ->weight(FontWeight::Bold),
                        TextEntry::make('openedBy.name')
                            ->label('Dibuka Oleh')
                            ->placeholder('-'),
                        TextEntry::make('opened_at')
                            ->label('Dibuka')
                            ->dateTime(),
                        TextEntry::make('closedBy.name')
                            ->label('Ditutup Oleh')
                            ->placeholder('-'),
                        TextEntry::make('closed_at')
                            ->label('Ditutup')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('declared_cash_amount')
                            ->label('Nominal Fisik Kas')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('expected_cash_amount')
                            ->label('Total Akumulasi Cashdrawer')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('difference')
                            ->label('Selisih')
                            ->numeric()
                            ->color(fn (mixed $state): string => match (true) {
                                (int) ($state ?? 0) < 0 => 'danger',
                                (int) ($state ?? 0) > 0 => 'warning',
                                default => 'success',
                            })
                            ->placeholder('-'),
                        TextEntry::make('opening_note')
                            ->label('Catatan Pembukaan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Keterangan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Ringkasan Akumulasi')
                    ->schema(function (CashDrawerShift $record): array {
                        $summary = app(CashDrawerService::class)->computeClosing($record);

                        return [
                            TextEntry::make('summary_cash_transactions')
                                ->label('Transaksi Cash')
                                ->state(fn (): string => format_rupiah($summary['total_cash_transactions'])),
                            TextEntry::make('summary_cash_income')
                                ->label('Pemasukan (Cashdrawer)')
                                ->state(fn (): string => format_rupiah($summary['total_cash_income'])),
                            TextEntry::make('summary_cash_expense')
                                ->label('Pengeluaran (Cashdrawer)')
                                ->state(fn (): string => format_rupiah($summary['total_cash_expense'])),
                            TextEntry::make('summary_expected_drawer')
                                ->label('Total Akumulasi Cashdrawer')
                                ->state(fn (): string => format_rupiah($summary['expected_drawer']))
                                ->weight(FontWeight::Bold),
                        ];
                    })
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                    ]),
            ]);
    }
}
