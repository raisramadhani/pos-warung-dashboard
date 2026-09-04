<?php

namespace App\Services;

use App\Enums\CashFlows\CashFlowType;
use App\Enums\Payrolls\PayrollStatus;
use App\Models\CashFlows\CashFlow;
use App\Models\Inventories\AssetDepreciation;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    /**
     * Menghitung Laporan Laba Rugi untuk rentang periode dan outlet terpilih.
     *
     * Rumus (disesuaikan fitur POS):
     * - Pendapatan Penjualan : SUM(transactions.total_amount) — outlet saja
     * - HPP                  : SUM(transaction_items.quantity x product_data.cost_price)
     * - Laba Kotor           : Pendapatan - HPP
     * - Pendapatan Lain-lain : SUM(cash_flows type=income)
     * - Beban Gaji           : SUM(payrolls status=Paid, periode overlap)
     * - Beban Operasional    : SUM(cash_flows type=expense)
     * - Beban Penyusutan     : SUM(asset_depreciations.depreciation_amount)
     * - Laba/Rugi Bersih     : Laba Kotor + Pendapatan Lain - Total Beban
     *
     * Revenue & HPP hanya dihitung untuk outlet (merchantsOnly). CashFlow,
     * Payroll, dan Penyusutan mencakup gudang + outlet. Bila merchantId diisi,
     * seluruh komponen dibatasi ke merchant tersebut.
     *
     * @return array{
     *     revenue: int,
     *     cogs: int,
     *     gross_profit: int,
     *     other_income: int,
     *     payroll_expense: int,
     *     operating_expense: int,
     *     depreciation_expense: int,
     *     total_expense: int,
     *     net_profit: int,
     * }
     */
    public function compute(Carbon $start, Carbon $end, ?int $merchantId = null): array
    {
        $transactionQuery = Transaction::query()
            ->whereBetween('transaction_at', [$start, $end])
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->when($merchantId, fn (Builder $query) => $query->where('merchant_id', $merchantId));

        $revenue = (int) (clone $transactionQuery)->sum('total_amount');
        $cogs = $this->sumCogs(clone $transactionQuery);

        $cashFlowQuery = CashFlow::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($merchantId, fn (Builder $query) => $query->where('merchant_id', $merchantId));

        $otherIncome = (int) (clone $cashFlowQuery)
            ->where('type', CashFlowType::Income)
            ->sum('amount');

        $operatingExpense = (int) (clone $cashFlowQuery)
            ->where('type', CashFlowType::Expense)
            ->sum('amount');

        $payrollExpense = (int) Payroll::query()
            ->where('status', PayrollStatus::Paid)
            ->where('period_start', '<=', $end)
            ->where('period_end', '>=', $start)
            ->when($merchantId, fn (Builder $query) => $query->where('merchant_id', $merchantId))
            ->sum('total_amount');

        $depreciationExpense = (int) AssetDepreciation::query()
            ->whereBetween('period_date', [$start->toDateString(), $end->toDateString()])
            ->when($merchantId, fn (Builder $query) => $query->whereHas('asset', fn (Builder $query) => $query->where('merchant_id', $merchantId)))
            ->sum('depreciation_amount');

        $grossProfit = $revenue - $cogs;
        $totalExpense = $operatingExpense + $payrollExpense + $depreciationExpense;
        $netProfit = $grossProfit + $otherIncome - $totalExpense;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'other_income' => $otherIncome,
            'payroll_expense' => $payrollExpense,
            'operating_expense' => $operatingExpense,
            'depreciation_expense' => $depreciationExpense,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * HPP = SUM(quantity x cost_price) dari transaction_items pada transaksi
     * yang terfilter (rentang + outlet). cost_price diambil dari
     * product_data (JSON) saat transaksi agar historis.
     */
    private function sumCogs(Builder $transactionQuery): int
    {
        $costPrice = "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(product_data, '$.cost_price')) AS DECIMAL(20,2)), 0)";

        return (int) TransactionItem::query()
            ->whereIn('transaction_id', $transactionQuery->select('transactions.id'))
            ->sum(DB::raw("quantity * {$costPrice}"));
    }
}
