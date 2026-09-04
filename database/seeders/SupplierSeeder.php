<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Supplier Alpha', 'contact_person' => 'Budi Santoso', 'email' => 'alpha@example.com', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 1, Jakarta', 'is_active' => true],
            ['name' => 'Supplier Beta', 'contact_person' => 'Siti Rahayu', 'email' => 'beta@example.com', 'phone' => '082345678901', 'address' => 'Jl. Sudirman No. 2, Bandung', 'is_active' => true],
            ['name' => 'Supplier Gamma', 'contact_person' => 'Ahmad Hidayat', 'email' => 'gamma@example.com', 'phone' => '083456789012', 'address' => 'Jl. Diponegoro No. 3, Surabaya', 'is_active' => true],
            ['name' => 'Supplier Delta', 'contact_person' => 'Dewi Lestari', 'email' => 'delta@example.com', 'phone' => '084567890123', 'address' => 'Jl. Gajah Mada No. 4, Yogyakarta', 'is_active' => true],
            ['name' => 'Supplier Epsilon', 'contact_person' => 'Rudi Hartono', 'email' => 'epsilon@example.com', 'phone' => '085678901234', 'address' => 'Jl. Pahlawan No. 5, Semarang', 'is_active' => true],
            ['name' => 'Supplier Zeta', 'contact_person' => 'Fitri Handayani', 'email' => 'zeta@example.com', 'phone' => '086789012345', 'address' => 'Jl. Imam Bonjol No. 6, Medan', 'is_active' => true],
            ['name' => 'Supplier Eta', 'contact_person' => 'Hendra Gunawan', 'email' => 'eta@example.com', 'phone' => '087890123456', 'address' => 'Jl. A. Yani No. 7, Makassar', 'is_active' => true],
            ['name' => 'Supplier Theta', 'contact_person' => null, 'email' => null, 'phone' => null, 'address' => null, 'is_active' => false],
            ['name' => 'Supplier Iota', 'contact_person' => 'Agus Wijaya', 'email' => 'iota@example.com', 'phone' => '089012345678', 'address' => 'Jl. Sisingamangaraja No. 9, Palembang', 'is_active' => false],
            ['name' => 'Supplier Kappa', 'contact_person' => 'Rina Marlina', 'email' => 'kappa@example.com', 'phone' => '090123456789', 'address' => 'Jl. Teuku Umar No. 10, Denpasar', 'is_active' => false],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::factory()->create($supplier);
        }
    }
}
