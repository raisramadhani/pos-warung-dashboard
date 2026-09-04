<?php

namespace App\Filament\Admin\Widgets\ProfitLoss;

use App\Services\ProfitLossService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ProfitLossStatementWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.profit-loss-statement';

    /**
     * @return array{statement: array<string, int>, start: Carbon, end: Carbon}
     */
    public function getViewData(): array
    {
        $filters = $this->pageFilters ?? [];

        $period = $filters['period'] ?? null;
        $merchantId = isset($filters['merchant_id'])
            ? (int) $filters['merchant_id']
            : null;

        [$start, $end] = $this->resolvePeriod($period);

        $statement = app(ProfitLossService::class)->compute($start, $end, $merchantId);

        return [
            'statement' => $statement,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function resolvePeriod(?string $period): array
    {
        if (filled($period)) {
            try {
                return parse_period($period);
            } catch (\Throwable) {
                // fallback ke bulan ini bila format tidak valid
            }
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }
}
