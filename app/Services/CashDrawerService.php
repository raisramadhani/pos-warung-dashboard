<?php

namespace App\Services;

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Enums\CashFlows\CashFlowType;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\CashFlows\CashFlow;
use App\Models\Transactions\Transaction;
use Illuminate\Support\Carbon;

class CashDrawerService
{
    public function hasOpenShift(int $merchantId): bool
    {
        return CashDrawerShift::query()
            ->where('merchant_id', $merchantId)
            ->where('status', CashDrawerShiftStatus::Open)
            ->exists();
    }

    public function getOpenShift(int $merchantId): ?CashDrawerShift
    {
        return CashDrawerShift::query()
            ->where('merchant_id', $merchantId)
            ->where('status', CashDrawerShiftStatus::Open)
            ->first();
    }

    /**
     * @return array{
     *     opening_amount: int,
     *     total_cash_income: int,
     *     total_cash_expense: int,
     *     total_cash_transactions: int,
     *     expected_drawer: int,
     * }
     */
    public function computeClosing(CashDrawerShift $shift): array
    {
        $startedAt = $shift->opened_at ?? $shift->created_at;
        $closedAt = $shift->closed_at ?? now();

        $totalCashIncome = (int) CashFlow::query()
            ->where('merchant_id', $shift->merchant_id)
            ->where('type', CashFlowType::Income)
            ->where('affects_cash_drawer', true)
            ->whereDate('transaction_date', '>=', $startedAt)
            ->whereDate('transaction_date', '<=', $closedAt)
            ->sum('amount');

        $totalCashExpense = (int) CashFlow::query()
            ->where('merchant_id', $shift->merchant_id)
            ->where('type', CashFlowType::Expense)
            ->where('affects_cash_drawer', true)
            ->whereDate('transaction_date', '>=', $startedAt)
            ->whereDate('transaction_date', '<=', $closedAt)
            ->sum('amount');

        $totalCashTransactions = $this->totalCashTransactions($shift, $startedAt, $closedAt);

        $openingAmount = $shift->opening_amount;
        // amount pemasukan positif dan pengeluaran negatif, jadi total arus kas = income + expense
        $expectedDrawer = $openingAmount + $totalCashIncome + $totalCashExpense + $totalCashTransactions;

        return [
            'opening_amount' => $openingAmount,
            'total_cash_income' => $totalCashIncome,
            'total_cash_expense' => $totalCashExpense,
            'total_cash_transactions' => $totalCashTransactions,
            'expected_drawer' => $expectedDrawer,
        ];
    }

    /**
     * Total uang tunai dari transaksi penjualan ber-payment_method 'cash'
     * dalam rentang shift. Perhitungan didelegasikan ke model Transaction
     * (Transaction::sumCashBetween) agar query transaksi hidup di domain
     * transaksi, bukan di service cashdrawer.
     */
    public function totalCashTransactions(CashDrawerShift $shift, ?Carbon $startedAt = null, ?Carbon $closedAt = null): int
    {
        $startedAt ??= $shift->opened_at ?? $shift->created_at;
        $closedAt ??= $shift->closed_at ?? now();

        return Transaction::sumCashBetween($shift->merchant_id, $startedAt, $closedAt);
    }
}
