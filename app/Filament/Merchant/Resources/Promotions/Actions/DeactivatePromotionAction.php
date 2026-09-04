<?php

namespace App\Filament\Merchant\Resources\Promotions\Actions;

use App\Models\Promotions\Promotion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DeactivatePromotionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'deactivatePromotion';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Nonaktifkan')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Nonaktifkan Promo')
            ->modalDescription('Nonaktifkan promo ini? Promo tidak akan dipakai pada transaksi POS.')
            ->modalSubmitActionLabel('Ya, Nonaktifkan')
            ->visible(fn (Promotion $record): bool => (bool) $record->is_active)
            ->action(function (Promotion $record): void {
                $record->update(['is_active' => false]);

                Notification::make()
                    ->title('Promo dinonaktifkan')
                    ->body('Promo tidak aktif dan tidak akan dipakai pada transaksi POS.')
                    ->success()
                    ->send();
            });
    }
}
