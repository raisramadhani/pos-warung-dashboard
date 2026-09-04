<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        foreach (range(1, 3) as $i) {
            User::factory()->create([
                'name' => "SPV {$i}",
                'username' => "spv{$i}",
                'email' => "spv{$i}@example.com",
                'role' => RoleType::SuperAdmin,
                'password' => $password,
            ]);
        }

        $this->call([
            SupplierSeeder::class,       // Master Suppliers
            MerchantSeeder::class,       // Master Cabang (Bebek Ledok Karanganyar 1/2/3)
            CategorySeeder::class,       // Kategori tiap cabang
            ItemSeeder::class,           // Data fisik bahan baku & alat
            ProductSeeder::class,        // Menu POS
            PurchaseOrderSeeder::class,   // Penerimaan stok bahan dari supplier ke pusat
            DistributionSeeder::class,   // Distribusi stok pusat ke cabang
            TransactionSeeder::class,    // Simulasi transaksi memotong stok cabang
            // AttendanceSeeder::class,     // Kehadiran karyawan per merchant
            PayrollSeeder::class,        // Penggajian dengan bonus transaksi
            AssetSeeder::class,          // Pencatatan aset mesin & penyusutan
            StockOpnameSeeder::class,    // Sesi stock opname di merchant
            CashFlowSeeder::class,       // Kas masuk/keluar operasional cabang
        ]);
    }
}
