<?php

namespace Database\Seeders;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        Merchant::create([
            'name' => 'Gudang Bebek Ledok',
            'type' => MerchantType::Warehouse,
            'slug' => 'gudang-bebek-ledok',
            'avatar_path' => null,
            'address' => 'Gudang Bebek Ledok',
            'latitude' => null,
            'longitude' => null,
            'ownership_type' => OwnershipType::Main,
            'current_status' => MerchantStatus::Active,
        ]);

        $merchants = [
            [
                'name' => 'Bebek Ledok Karanganyar',
                'address' => 'Jl. Lawu, Karanganyar',
                'latitude' => '-7.5961000',
                'longitude' => '110.9502000',
            ],
        ];

        foreach ($merchants as $data) {
            $i = 1; // hanya ada 1 outlet → outlet1@example.com

            $merchant = Merchant::create([
                'name' => $data['name'],
                'type' => MerchantType::Merchant,
                'slug' => Str::slug($data['name']),
                'avatar_path' => null,
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'ownership_type' => OwnershipType::Main,
                'current_status' => MerchantStatus::Active,
            ]);

            $user = User::factory()->create([
                'name' => "Outlet {$data['name']}",
                'username' => "outlet{$i}",
                'email' => "outlet{$i}@example.com",
                'role' => RoleType::Merchant,
                'password' => $password,
            ]);

            $user->merchants()->attach($merchant);
        }
    }
}
