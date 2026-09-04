<?php

namespace Database\Seeders;

use App\Enums\Inventories\ItemType;
use App\Models\Inventories\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Cup ETD 16oz', 'type' => ItemType::RawMaterial, 'unit' => 'pcs'],
            ['name' => 'Cup ETD 22oz', 'type' => ItemType::RawMaterial, 'unit' => 'pcs'],
            ['name' => 'Tutup Datar', 'type' => ItemType::RawMaterial, 'unit' => 'pcs'],
            ['name' => 'Sedotan (Pack)', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Lid Sealer', 'type' => ItemType::RawMaterial, 'unit' => 'roll'],
            ['name' => 'Refill Galon', 'type' => ItemType::RawMaterial, 'unit' => 'galon'],

            ['name' => 'Teh Racik', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Susu UHT', 'type' => ItemType::RawMaterial, 'unit' => 'liter'],
            ['name' => 'Susu SKM', 'type' => ItemType::RawMaterial, 'unit' => 'kaleng'],
            ['name' => 'Jeruk Malang', 'type' => ItemType::RawMaterial, 'unit' => 'pcs'],
            ['name' => 'Jeruk Kampul', 'type' => ItemType::RawMaterial, 'unit' => 'pcs'],
            ['name' => 'Gula', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],
            ['name' => 'Es Batu Kristal', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],

            ['name' => 'Perasa Jasmine Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Blueberry Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Leci Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Thai Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Green Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Passion Fruit Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Cappucino Machiato', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Chocomelt Machiato', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Taro Machiato', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Redvelvet Machiato', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Strawberry Fruit', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Grape Fruit', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Mango Fruit', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Milk Tea', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa Leci Fruit', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Perasa ButterScooth', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Matcha', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Bubuk Choco Original', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],

            ['name' => 'Mie Samyang', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Mie Seblak', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Mie Original', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],

            ['name' => 'Mesin Cup Sealer', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Dispenser Teh', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Cooler Box', 'type' => ItemType::Tool, 'unit' => 'unit'],
        ];

        foreach ($items as $data) {
            Item::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'type' => $data['type'],
                'unit' => $data['unit'],
                'is_active' => true,
            ]);
        }
    }
}
