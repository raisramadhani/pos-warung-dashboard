<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::all();
        $categories = ['Paket Ekoran', 'Porsian', 'Tambahan', 'Minuman'];

        foreach ($merchants as $merchant) {
            foreach ($categories as $categoryName) {
                Category::create([
                    'merchant_id' => $merchant->id,
                    'name' => $categoryName,
                    'slug' => Str::slug($merchant->name.' '.$categoryName),
                    'is_active' => true,
                ]);
            }
        }
    }
}
