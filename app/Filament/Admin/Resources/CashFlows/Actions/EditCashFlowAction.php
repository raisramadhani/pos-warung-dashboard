<?php

namespace App\Filament\Admin\Resources\CashFlows\Actions;

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\CashFlows\Schemas\CashFlowForm;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditCashFlowAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'edit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->slideOver()
            ->modalHeading('Edit Keuangan')
            ->form(CashFlowForm::fields())
            ->fillForm(fn (CashFlow $record): array => [
                'merchant_id' => $record->merchant_id,
                'type' => $record->type->value,
                'amount' => abs($record->amount),
                'transaction_date' => $record->transaction_date,
                'affects_cash_drawer' => $record->affects_cash_drawer,
                'description' => $record->description,
            ])
            ->action(function (CashFlow $record, array $data): void {
                $merchant = Merchant::query()->find($data['merchant_id'] ?? $record->merchant_id);
                if ($merchant?->type === MerchantType::Warehouse) {
                    $data['affects_cash_drawer'] = false;
                }

                $data['amount'] = CashFlow::withSign((int) $data['amount'], $data['type']);

                $record->update($data);

                Notification::make()
                    ->title('Keuangan diperbarui')
                    ->success()
                    ->send();
            });
    }
}
