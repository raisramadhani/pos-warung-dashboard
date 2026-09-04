<?php

namespace App\Observers;

use App\Models\Inventories\PurchaseOrder;
use App\Services\DocumentNumberService;

class PurchaseOrderObserver
{
    private const PREFIX = 'PO-';

    public function creating(PurchaseOrder $po): void
    {
        if (empty($po->po_number)) {
            /** @var DocumentNumberService $service */
            $service = app(DocumentNumberService::class);
            $po->po_number = $service->generate(
                prefix: self::PREFIX,
                merchantId: $po->merchant_id,
                modelClass: PurchaseOrder::class,
                field: 'po_number',
            );
        }
    }
}
