<?php

namespace App\Filament\Merchant\Resources\Promotions\Actions;

use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ActivatePromotionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activatePromotion';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Aktifkan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Aktifkan Promo')
            ->modalDescription('Aktifkan promo ini agar bisa dipakai pada transaksi POS?')
            ->modalSubmitActionLabel('Ya, Aktifkan')
            ->visible(fn (Promotion $record): bool => ! $record->is_active)
            ->action(function (Promotion $record): void {
                $conflictedNames = $this->conflictedProductNames($record);

                if ($conflictedNames !== []) {
                    Notification::make()
                        ->title('Aktivasi promo ditolak')
                        ->body('Produk sudah dipakai promo aktif lain: '.implode(', ', $conflictedNames))
                        ->danger()
                        ->send();

                    return;
                }

                $record->update(['is_active' => true]);

                Notification::make()
                    ->title('Promo diaktifkan')
                    ->body('Promo sekarang aktif dan bisa dipakai pada transaksi POS.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private function conflictedProductNames(Promotion $record): array
    {
        $conditionProductIds = $record->conditions()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($conditionProductIds === []) {
            return [];
        }

        $productIds = Product::query()
            ->whereIn('id', $conditionProductIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($productIds === []) {
            return [];
        }

        return Product::query()
            ->where('merchant_id', $record->merchant_id)
            ->whereIn('id', $productIds)
            ->whereIn('id', function ($query) use ($record): void {
                $query->select('promotion_conditions.product_id')
                    ->from('promotion_conditions')
                    ->join('promotions', 'promotions.id', '=', 'promotion_conditions.promotion_id')
                    ->where('promotions.merchant_id', $record->merchant_id)
                    ->where('promotions.is_active', true)
                    ->whereNull('promotions.deleted_at')
                    ->where('promotions.id', '!=', $record->id);
            })
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
