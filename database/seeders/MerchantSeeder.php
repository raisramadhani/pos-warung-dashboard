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
            'name' => 'Gudang Pusat',
            'type' => MerchantType::Warehouse,
            'slug' => 'gudang-pusat',
            'avatar_path' => null,
            'address' => 'Gudang Pusat',
            'latitude' => null,
            'longitude' => null,
            'ownership_type' => OwnershipType::Main,
            'current_status' => MerchantStatus::Active,
        ]);

        $merchants = [
            [
                'name' => 'SOLO 1',
                'address' => 'Surakarta',
                'latitude' => '-7.5581339',
                'longitude' => '110.7716824',
            ],
            [
                'name' => 'SOLO 2',
                'address' => 'Surakarta',
                'latitude' => '-7.5404315',
                'longitude' => '110.8206136',
            ],
            [
                'name' => 'SOLO 3',
                'address' => 'Surakarta',
                'latitude' => '-7.5815958',
                'longitude' => '110.8192241',
            ],
        ];

        foreach ($merchants as $index => $data) {
            $i = $index + 1;

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
