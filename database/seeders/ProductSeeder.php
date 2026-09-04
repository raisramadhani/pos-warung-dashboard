<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::pluck('id', 'name');
        $merchants = Merchant::all();

        $menus = [
            'Racik Series' => [
                ['name' => 'Es Teh Jumbo', 'price' => 4000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Teh Racik' => 2, 'Gula' => 2, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Teh Reguler', 'price' => 3000, 'materials' => ['Cup ETD 16oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Teh Racik' => 1, 'Gula' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Teh Kampul', 'price' => 5000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Teh Racik' => 1, 'Jeruk Kampul' => 1, 'Gula' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Teh Hijau', 'price' => 5000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Green Tea' => 1, 'Gula' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Jeruk Peras', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Jeruk Malang' => 2, 'Gula' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Teh Hangat', 'price' => 3000, 'materials' => ['Cup ETD 16oz' => 1, 'Tutup Datar' => 1, 'Sedotan (Pack)' => 1, 'Teh Racik' => 1, 'Gula' => 1]],
                ['name' => 'Teh Kampul Hangat', 'price' => 4000, 'materials' => ['Cup ETD 16oz' => 1, 'Tutup Datar' => 1, 'Sedotan (Pack)' => 1, 'Teh Racik' => 1, 'Jeruk Kampul' => 1, 'Gula' => 1]],
                ['name' => 'Jeruk Peras Hangat', 'price' => 4000, 'materials' => ['Cup ETD 16oz' => 1, 'Tutup Datar' => 1, 'Sedotan (Pack)' => 1, 'Jeruk Malang' => 2, 'Gula' => 1]],
            ],
            'Macchiatto Series' => [
                ['name' => 'Es Cappucino Macchiatto', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Bubuk Cappucino Machiato' => 1, 'Susu UHT' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Chocomelt Macchiatto', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Bubuk Chocomelt Machiato' => 1, 'Susu UHT' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Taro Macchiatto', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Bubuk Taro Machiato' => 1, 'Susu UHT' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Redvelvet Macchiatto', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Bubuk Redvelvet Machiato' => 1, 'Susu UHT' => 1, 'Es Batu Kristal' => 1]],
            ],
            'Fruit Series' => [
                ['name' => 'Es Strawberry Fruit', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Strawberry Fruit' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Mango Fruit', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Mango Fruit' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Grape Fruit', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Grape Fruit' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Leci Tea', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Leci Tea' => 1, 'Es Batu Kristal' => 1]],
                ['name' => 'Es Thai Tea', 'price' => 6000, 'materials' => ['Cup ETD 22oz' => 1, 'Lid Sealer' => 1, 'Sedotan (Pack)' => 1, 'Perasa Thai Tea' => 1, 'Susu SKM' => 1, 'Es Batu Kristal' => 1]],
            ],
            'Mie Desa' => [
                ['name' => 'Mie Samyang Desa', 'price' => 8000, 'materials' => ['Mie Samyang' => 1]],
                ['name' => 'Mie Seblak Desa', 'price' => 8000, 'materials' => ['Mie Seblak' => 1]],
                ['name' => 'Mie Original Desa', 'price' => 8000, 'materials' => ['Mie Original' => 1]],
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
                    $product = Product::create([
                        'merchant_id' => $merchant->id,
                        'category_id' => $categoryId,
                        'name' => $menu['name'],
                        'slug' => Str::slug($merchant->name.' '.$menu['name']),
                        'selling_price' => $menu['price'],
                        'is_active' => true,
                        // Sort order akan increment misal: 1, 2, 3.. 8, 9, 10.. dst
                        'sort_order' => $sortOrder++,
                    ]);

                    foreach ($menu['materials'] as $itemName => $qty) {
                        if (isset($items[$itemName])) {
                            $product->productMaterials()->create([
                                'item_id' => $items[$itemName],
                                'quantity_required' => $qty,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
