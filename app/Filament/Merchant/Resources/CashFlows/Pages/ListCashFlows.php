<?php

namespace App\Filament\Merchant\Resources\CashFlows\Pages;

use App\Filament\Merchant\Resources\CashFlows\CashFlowResource;
use App\Filament\Merchant\Resources\CashFlows\Schemas\CashFlowForm;
use App\Models\CashFlows\CashFlow;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCashFlows extends ListRecords
{
    protected static string $resource = CashFlowResource::class;

    protected ?string $heading = 'Keuangan';

    protected ?string $subheading = 'Daftar pemasukan dan pengeluaran untuk merchant ini';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Keuangan')
                ->slideOver()
                ->modalHeading('Tambah Keuangan')
                ->form(CashFlowForm::fields())
                ->using(function (array $data): CashFlow {
                    $data['merchant_id'] = filament()->getTenant()?->getKey();
                    $data['amount'] = CashFlow::withSign((int) $data['amount'], $data['type']);

                    $cashFlow = CashFlow::create($data);

                    Notification::make()
                        ->title('Keuangan dibuat')
                        ->success()
                        ->send();

                    return $cashFlow;
                }),
        ];
    }
}
