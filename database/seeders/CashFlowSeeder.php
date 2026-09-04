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
            'Bebek Ledok Karanganyar' => [
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan listrik bulanan', 'amount' => 850000, 'transaction_date' => '2026-01-10'],
                ['type' => CashFlowType::Expense, 'description' => 'Tagihan PDAM bulanan', 'amount' => 150000, 'transaction_date' => '2026-01-15'],
                ['type' => CashFlowType::Expense, 'description' => 'Sewa tempat usaha', 'amount' => 3000000, 'transaction_date' => '2026-02-01'],
                ['type' => CashFlowType::Expense, 'description' => 'Gaji karyawan', 'amount' => 2500000, 'transaction_date' => '2026-02-25'],
                ['type' => CashFlowType::Income, 'description' => 'Setoran dana awal modal', 'amount' => 5000000, 'transaction_date' => '2026-01-05'],
                ['type' => CashFlowType::Expense, 'description' => 'Pembelian bahan baku dapur', 'amount' => 450000, 'transaction_date' => '2026-03-05'],
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
