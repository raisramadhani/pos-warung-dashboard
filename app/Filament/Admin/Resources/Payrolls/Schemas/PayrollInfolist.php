<?php

namespace App\Filament\Admin\Resources\Payrolls\Schemas;

use App\Enums\Payrolls\PayrollStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Slip Gaji')
                    ->description('Data karyawan dan periode penggajian')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Karyawan'),
                        TextEntry::make('merchant.name')
                            ->label('Outlet')
                            ->placeholder('-'),
                        TextEntry::make('period_start')
                            ->label('Periode Mulai')
                            ->date('d F Y'),
                        TextEntry::make('period_end')
                            ->label('Periode Akhir')
                            ->date('d F Y'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->tooltip(fn (PayrollStatus $state) => $state->getDescription()),
                        TextEntry::make('total_amount')
                            ->label('Total')
                            ->numeric(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ]),
            ]);
    }
}
