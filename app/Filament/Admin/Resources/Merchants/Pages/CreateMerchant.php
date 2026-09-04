<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Enums\Merchants\MerchantType;
use App\Enums\RoleType;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @extends CreateRecord<Merchant>
 */
class CreateMerchant extends CreateRecord
{
    protected static string $resource = MerchantResource::class;

    protected ?string $heading = 'Tambah Outlet Baru';

    protected ?string $subheading = 'Buat Outlet Baru';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Tipe outlet selalu merchant — pembuatan gudang tidak diperbolehkan dari sini.
        $data['type'] = MerchantType::Merchant->value;

        // Akun user yang dibuat bersama outlet ini selalu ber-role merchant.
        $data['user']['role'] = RoleType::Merchant->value;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $merchantData = Arr::except($data, ['user']);

        $merchant = parent::handleRecordCreation($merchantData);

        $userData = $data['user'] ?? [];

        if (filled($userData)) {
            /** @var User $user */
            $user = User::query()->create($userData);

            $merchant->members()->attach($user);
        }

        return $merchant;
    }
}
