<?php

namespace App\Observers;

use App\Models\Transactions\Transaction;
use App\Services\DocumentNumberService;
use Illuminate\Support\Carbon;

class TransactionObserver
{
    private const PREFIX = 'TRX-';

    public function creating(Transaction $transaction): void
    {
        if (empty($transaction->transaction_number)) {
            /** @var DocumentNumberService $service */
            $service = app(DocumentNumberService::class);
            $transaction->transaction_number = $service->generate(
                prefix: self::PREFIX,
                merchantId: $transaction->merchant_id,
                modelClass: Transaction::class,
                field: 'transaction_number',
            );
        }

        if (empty($transaction->transaction_at)) {
            $transaction->transaction_at = Carbon::now();
        }
    }
}
