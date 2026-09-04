<?php

namespace App\Services;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Payrolls\Payroll;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PayrollDuplicateService
{
    /**
     * Find existing payrolls for a user whose period overlaps the given range.
     * Canceled payrolls are excluded — they no longer count as duplicates.
     *
     * @return Collection<int, Payroll>
     */
    public function forUser(int $userId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return Payroll::query()
            ->with('items')
            ->where('user_id', $userId)
            ->where('status', '!=', PayrollStatus::Canceled->value)
            ->whereDate('period_start', '<=', $periodEnd->toDateString())
            ->whereDate('period_end', '>=', $periodStart->toDateString())
            ->orderByDesc('period_start')
            ->get();
    }
}
