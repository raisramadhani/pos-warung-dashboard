<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::all();

        $menus = [
            'Paket Ekoran' => [
                ['name' => 'Paket Bebek 1 Ekor Komplit', 'price' => 110000],
                ['name' => 'Paket Ayam 1 Ekor Komplit', 'price' => 100000],
            ],
            'Porsian' => [
                ['name' => 'Porsian Bebek Kremes + Teh', 'price' => 28000],
                ['name' => 'Porsian Ayam Kremes + Teh', 'price' => 25000],
            ],
            'Tambahan' => [
                ['name' => 'Bebek Goreng Potongan', 'price' => 21000],
                ['name' => 'Ayam Goreng Potongan', 'price' => 18000],
                ['name' => 'Nasi 1 Bakul', 'price' => 15000],
                ['name' => 'Nasi Putih Porsi', 'price' => 5000],
                ['name' => 'Trancam Segar', 'price' => 6000],
                ['name' => 'Peyek, Tempe & Terong Goreng', 'price' => 8000],
                ['name' => 'Sambal Bawang / Hijau Ekstra', 'price' => 3000],
            ],
            'Minuman' => [
                ['name' => 'Teh 1 Porong (Teko Blirik)', 'price' => 15000],
                ['name' => 'Es Teh Manis / Hangat', 'price' => 4000],
                ['name' => 'Es Jeruk / Jeruk Hangat', 'price' => 7000],
            ],
        ];

        foreach ($merchants as $merchant) {
            $categories = Category::where('merchant_id', $merchant->id)->pluck('id', 'name');

            // sehingga angkanya terus berlanjut untuk satu merchant yang sama.
            $sortOrder = 1;

            foreach ($menus as $categoryName => $products) {
                if (! isset($categories[$categoryName])) {
                    continue;
                }

                $categoryId = $categories[$categoryName];

                foreach ($products as $menu) {
                    Product::create([
                        'merchant_id' => $merchant->id,
                        'category_id' => $categoryId,
                        'name' => $menu['name'],
                        'slug' => Str::slug($merchant->name.' '.$menu['name']),
                        'selling_price' => $menu['price'],
                        'is_active' => true,
                        // Sort order akan increment misal: 1, 2, 3.. 8, 9, 10.. dst
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }
    }
}
