<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Actions;

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Models\CashDrawer\CashDrawerShift;
use App\Services\CashDrawerService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OpenShiftAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'openShift';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Buka Shift')
            ->icon('tabler-lock-open')
            ->color('primary')
            ->visible(function (): bool {
                $tenant = filament()->getTenant();

                if (! $tenant) {
                    return false;
                }

                return ! app(CashDrawerService::class)->hasOpenShift($tenant->getKey());
            })
            ->slideOver()
            ->modalHeading('Buka Shift')
            ->modalSubmitActionLabel('Buka Shift')
            ->form([
                TextInput::make('opening_amount')
                    ->label('Saldo Awal Cashdrawer')
                    ->required()
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state)),
                Textarea::make('opening_note')
                    ->label('Catatan')
                    ->placeholder('Masukan catatan (opsional)')
                    ->nullable()
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();

                DB::transaction(function () use ($tenant, $data): void {
                    CashDrawerShift::query()->create([
                        'merchant_id' => $tenant?->getKey(),
                        'status' => CashDrawerShiftStatus::Open,
                        'opening_amount' => $data['opening_amount'],
                        'opening_note' => $data['opening_note'] ?? null,
                        'opened_by' => auth()->id(),
                        'opened_at' => now(),
                    ]);
                });

                Notification::make()
                    ->title('Shift kas dibuka')
                    ->body('Saldo awal cashdrawer telah dicatat.')
                    ->success()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
