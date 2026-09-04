<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Actions;

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Services\CashDrawerService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Livewire\Component;

class CloseShiftAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeShift';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tutup Shift')
            ->icon('tabler-lock')
            ->color('danger')
            ->visible(function (): bool {
                $tenant = filament()->getTenant();

                if (! $tenant) {
                    return false;
                }

                return app(CashDrawerService::class)->hasOpenShift($tenant->getKey());
            })
            ->slideOver()
            ->modalHeading('Tutup Shift')
            ->modalSubmitActionLabel('Tutup Shift')
            ->form(fn (): array => $this->formFields())
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();
                $service = app(CashDrawerService::class);

                $shift = $service->getOpenShift($tenant->getKey());

                if (! $shift) {
                    Notification::make()
                        ->title('Tidak ada shift aktif')
                        ->danger()
                        ->send();

                    return;
                }

                $summary = $service->computeClosing($shift);

                $shift->update([
                    'status' => CashDrawerShiftStatus::Closed,
                    'expected_cash_amount' => $summary['expected_drawer'],
                    'declared_cash_amount' => $data['declared_cash_amount'],
                    'difference' => $data['declared_cash_amount'] - $summary['expected_drawer'],
                    'closed_by' => auth()->id(),
                    'closed_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]);

                Notification::make()
                    ->title('Shift kas ditutup')
                    ->body('Akumulasi cashdrawer telah dicatat.')
                    ->success()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }

    /**
     * @return array<int, Grid|Placeholder|Textarea|TextInput>
     */
    private function formFields(): array
    {
        $tenant = filament()->getTenant();
        $service = app(CashDrawerService::class);
        $shift = $tenant ? $service->getOpenShift($tenant->getKey()) : null;
        $summary = $shift ? $service->computeClosing($shift) : null;

        $expectedDrawer = (int) ($summary['expected_drawer'] ?? 0);

        return [
            Grid::make(3)
                ->schema([
                    Placeholder::make('opening_amount')
                        ->label('Saldo Awal')
                        ->content(fn (): string => format_rupiah($summary['opening_amount'] ?? 0)),
                    Placeholder::make('total_cash_transactions')
                        ->label('Transaksi Cash')
                        ->content(fn (): string => format_rupiah($summary['total_cash_transactions'] ?? 0)),
                    Placeholder::make('total_cash_income')
                        ->label('Pemasukan')
                        ->content(fn (): string => format_rupiah($summary['total_cash_income'] ?? 0)),
                    Placeholder::make('total_cash_expense')
                        ->label('Pengeluaran')
                        ->content(fn (): string => format_rupiah($summary['total_cash_expense'] ?? 0)),
                    Placeholder::make('expected_drawer')
                        ->label('Total Akumulasi Cashdrawer')
                        ->content(fn (): string => format_rupiah($expectedDrawer))
                        ->columnSpan(2),
                ]),
            TextInput::make('declared_cash_amount')
                ->label('Nominal Fisik Kas')
                ->required()
                ->prefix('Rp')
                ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                ->formatStateUsing(fn ($state) => format_quantity($state))
                ->dehydrateStateUsing(fn ($state) => to_number($state))
                ->suffixAction(
                    Action::make('setToExpected')
                        ->label('Isi sesuai akumulasi')
                        ->icon('tabler-checks')
                        ->tooltip('Isi sesuai total akumulasi cashdrawer')
                        ->action(function (Set $set) use ($expectedDrawer): void {
                            $set('declared_cash_amount', $expectedDrawer);
                        }),
                ),
            Textarea::make('notes')
                ->label('Keterangan')
                ->placeholder('Masukan keterangan (opsional)')
                ->nullable()
                ->rows(3),
        ];
    }
}
