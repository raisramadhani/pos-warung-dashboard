<?php

namespace App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard;

use App\Enums\Payments\PaymentMethod;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $tenantId = filament()->getTenant()?->getKey();

        $query = Transaction::query()
            ->where('merchant_id', $tenantId)
            ->when($this->getDateRange(), function (Builder $query, array $range): Builder {
                return $query->whereBetween('transaction_at', $range);
            });

        $totalRevenue = (int) (clone $query)->sum('total_amount');
        $transactionCount = (clone $query)->count();
        $averagePerTransaction = $transactionCount > 0 ? intdiv($totalRevenue, $transactionCount) : 0;

        $qrisCount = (clone $query)->where('payment_method', PaymentMethod::Qris)->count();
        $cashCount = (clone $query)->where('payment_method', PaymentMethod::Cash)->count();

        [$topProductName, $topProductQty] = $this->topSellingProduct($query);

        return [
            Stat::make('Pendapatan', format_rupiah($totalRevenue))
                ->description('Total pendapatan periode terpilih')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Jumlah Transaksi', $transactionCount)
                ->description('Total transaksi periode terpilih')
                ->icon('heroicon-o-receipt-percent')
                ->color('info'),
            Stat::make('Rata-rata / Transaksi', format_rupiah($averagePerTransaction))
                ->description('Rata-rata nilai per transaksi')
                ->icon('heroicon-o-calculator')
                ->color('warning'),
            Stat::make('QRIS', $qrisCount)
                ->description('Transaksi via QRIS')
                ->icon('heroicon-o-qr-code')
                ->color('info'),
            Stat::make('Cash', $cashCount)
                ->description('Transaksi via tunai')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Produk Terlaris', $topProductName)
                ->description($topProductQty > 0 ? $topProductQty.' pcs terjual' : 'Belum ada penjualan')
                ->icon('heroicon-o-fire')
                ->color('danger'),
        ];
    }

    /**
     * @return array{string, int} [nama produk terlaris, total qty terjual]
     */
    private function topSellingProduct(Builder $query): array
    {
        $top = TransactionItem::query()
            ->whereIn('transaction_id', (clone $query)->select('id'))
            ->selectRaw('COALESCE(product_id, 0) as product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->first();

        if (! $top || ! $top->product_id) {
            return ['-', 0];
        }

        $product = Product::query()
            ->withTrashed()
            ->whereKey($top->product_id)
            ->first();

        if (! $product) {
            return ['Produk', (int) $top->getAttribute('total_qty')];
        }

        return [
            $product->name,
            (int) $top->getAttribute('total_qty'),
        ];
    }

    /**
     * @return array{Carbon, Carbon}|null
     */
    private function getDateRange(): ?array
    {
        $value = $this->pageFilters['transaction_at'] ?? null;

        if (! $value) {
            return null;
        }

        [$from, $to] = explode(' - ', $value);

        return [
            Carbon::createFromFormat('d/m/Y', $from)->startOfDay(),
            Carbon::createFromFormat('d/m/Y', $to)->endOfDay(),
        ];
    }
}
