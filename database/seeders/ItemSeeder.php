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
            // Bahan baku utama (protein)
            ['name' => 'Bebek (ekor)', 'type' => ItemType::RawMaterial, 'unit' => 'ekor'],
            ['name' => 'Ayam (ekor)', 'type' => ItemType::RawMaterial, 'unit' => 'ekor'],

            // Karbohidrat & lauk pendamping
            ['name' => 'Nasi Putih', 'type' => ItemType::RawMaterial, 'unit' => 'porsi'],
            ['name' => 'Tahu', 'type' => ItemType::RawMaterial, 'unit' => 'potong'],
            ['name' => 'Tempe', 'type' => ItemType::RawMaterial, 'unit' => 'potong'],
            ['name' => 'Terong', 'type' => ItemType::RawMaterial, 'unit' => 'buah'],
            ['name' => 'Telur Ayam', 'type' => ItemType::RawMaterial, 'unit' => 'butir'],

            // Lalapan & sayuran
            ['name' => 'Kol', 'type' => ItemType::RawMaterial, 'unit' => 'ikat'],
            ['name' => 'Timun', 'type' => ItemType::RawMaterial, 'unit' => 'buah'],
            ['name' => 'Kacang Panjang', 'type' => ItemType::RawMaterial, 'unit' => 'ikat'],
            ['name' => 'Daun Kemangi', 'type' => ItemType::RawMaterial, 'unit' => 'ikat'],
            ['name' => 'Trancam Segar', 'type' => ItemType::RawMaterial, 'unit' => 'porsi'],

            // Bumbu, pelengkap & minyak
            ['name' => 'Peyek', 'type' => ItemType::RawMaterial, 'unit' => 'bungkus'],
            ['name' => 'Sambal Bawang', 'type' => ItemType::RawMaterial, 'unit' => 'porsi'],
            ['name' => 'Sambal Hijau', 'type' => ItemType::RawMaterial, 'unit' => 'porsi'],
            ['name' => 'Bumbu Dasar (Bawang Merah/Putih, Kemiri, Kunyit, Ketumbar)', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],
            ['name' => 'Serai & Daun Jeruk', 'type' => ItemType::RawMaterial, 'unit' => 'ikat'],
            ['name' => 'Kecap Manis', 'type' => ItemType::RawMaterial, 'unit' => 'botol'],
            ['name' => 'Tepung Kriuk/Kremes', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],
            ['name' => 'Minyak Goreng', 'type' => ItemType::RawMaterial, 'unit' => 'liter'],
            ['name' => 'Garam', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],

            // Minuman
            ['name' => 'Gula', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],
            ['name' => 'Teh Celup', 'type' => ItemType::RawMaterial, 'unit' => 'pack'],
            ['name' => 'Jeruk Peras', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],
            ['name' => 'Es Batu', 'type' => ItemType::RawMaterial, 'unit' => 'kg'],

            // Peralatan dapur (aset)
            ['name' => 'Wajan Besar', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Presto', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Kompor Gas', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Freezer Box', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Dandang Nasi', 'type' => ItemType::Tool, 'unit' => 'unit'],
            ['name' => 'Talenan & Pisau Dapur', 'type' => ItemType::Tool, 'unit' => 'set'],
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
