<?php

namespace Database\Seeders;

use App\Enums\CashFlows\CashFlowType;
use App\Enums\Merchants\MerchantType;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Seeder;

class CashFlowSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::where('type', MerchantType::Merchant)->get();

        $flowsByMerchant = [
            'Bebek Ledok Karanganyar 1' => [
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan listrik bulanan', 'amount' => 850000, 'transaction_date' => '2026-01-10'],
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan PDAM bulanan', 'amount' => 150000, 'transaction_date' => '2026-01-15'],
                ['type' => CashFlowType::Expense, 'description' => 'Sewa tempat usaha', 'amount' => 3000000, 'transaction_date' => '2026-02-01'],
                ['type' => CashFlowType::Expense, 'description' => 'Gaji karyawan', 'amount' => 2500000, 'transaction_date' => '2026-02-25'],
                ['type' => CashFlowType::Income, 'description' => 'Setoran dana awal modal', 'amount' => 5000000, 'transaction_date' => '2026-01-05'],
                ['type' => CashFlowType::Expense, 'description' => 'Pembelian bahan baku dapur', 'amount' => 450000, 'transaction_date' => '2026-03-05'],
            ],
            'Bebek Ledok Karanganyar 2' => [
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan listrik bulanan', 'amount' => 750000, 'transaction_date' => '2026-01-12'],
                ['type' => CashFlowType::Expense, 'description' => 'Perawatan peralatan dapur', 'amount' => 350000, 'transaction_date' => '2026-02-08'],
                ['type' => CashFlowType::Expense, 'description' => 'Sewa tempat usaha', 'amount' => 2800000, 'transaction_date' => '2026-02-01'],
                ['type' => CashFlowType::Income, 'description' => 'Setoran dana awal modal', 'amount' => 4500000, 'transaction_date' => '2026-01-08'],
                ['type' => CashFlowType::Expense, 'description' => 'Gaji karyawan', 'amount' => 2200000, 'transaction_date' => '2026-02-25'],
            ],
            'Bebek Ledok Karanganyar 3' => [
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan listrik bulanan', 'amount' => 900000, 'transaction_date' => '2026-01-14'],
                ['type' => CashFlowType::Expense, 'description' => 'Perbaikan peralatan produksi', 'amount' => 600000, 'transaction_date' => '2026-02-20'],
                ['type' => CashFlowType::Expense, 'description' => 'Sewa tempat usaha', 'amount' => 3200000, 'transaction_date' => '2026-02-01'],
                ['type' => CashFlowType::Income, 'description' => 'Setoran dana awal modal', 'amount' => 6000000, 'transaction_date' => '2026-01-06'],
                ['type' => CashFlowType::Expense, 'description' => 'Gaji karyawan', 'amount' => 2700000, 'transaction_date' => '2026-02-25'],
            ],
        ];

        foreach ($merchants as $merchant) {
            $flows = $flowsByMerchant[$merchant->name] ?? [];

            foreach ($flows as $flow) {
                $flow['amount'] = CashFlow::withSign($flow['amount'], $flow['type']);

                CashFlow::factory()->forMerchant($merchant)->create($flow);
            }
        }
    }
}
