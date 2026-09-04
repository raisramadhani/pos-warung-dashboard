<?php

namespace App\Observers;

use App\Models\CashDrawer\CashDrawerShift;
use App\Services\DocumentNumberService;

class CashDrawerShiftObserver
{
    private const PREFIX = 'CS-';

    public function creating(CashDrawerShift $shift): void
    {
        if (empty($shift->shift_number)) {
            /** @var DocumentNumberService $service */
            $service = app(DocumentNumberService::class);
            $shift->shift_number = $service->generate(
                prefix: self::PREFIX,
                merchantId: $shift->merchant_id,
                modelClass: CashDrawerShift::class,
                field: 'shift_number',
            );
        }
    }
}
