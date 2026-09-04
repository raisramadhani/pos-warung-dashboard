<?php

namespace App\Filament\Admin\Widgets\ProfitLoss\Concerns;

use Illuminate\Support\Carbon;

trait InteractsWithProfitLossFilters
{
    /**
     * @return array{Carbon, Carbon}
     */
    protected function getPeriodRange(): array
    {
        $period = $this->pageFilters['period'] ?? null;

        if (filled($period)) {
            try {
                return parse_period($period);
            } catch (\Throwable) {
                // fallback ke bulan ini bila format tidak valid
            }
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    protected function getMerchantId(): ?int
    {
        $merchantId = $this->pageFilters['merchant_id'] ?? null;

        return $merchantId !== null && $merchantId !== ''
            ? (int) $merchantId
            : null;
    }
}
