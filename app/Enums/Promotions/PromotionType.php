<?php

namespace App\Enums\Promotions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PromotionType: string implements HasColor, HasIcon, HasLabel
{
    case BundleFixedPrice = 'bundle_fixed_price';
    case BuyXGetY = 'buy_x_get_y';
    case FlashSalePrice = 'flash_sale_price';
    case PercentDiscount = 'percent_discount';
    case FixedDiscount = 'fixed_discount';

    /**
     * Alasan kenapa ada default adalah karena saat ini promo dilapangan adlaah Flash Sale Es Teh Jumbo harga 2500 di jam 7-9 pagi dan 10-12 malam,
     * Buy Es Teh Jumbo 1 Get 1 Free Es Teh Jumbo, Jumat Berkah setiap hari jumat beli 3 Es Teh Jumbo cuma 10rb dan meminimalisir bug
     * sehingga saat ini dibuat hanya tersedia ini saja dulu, walaupun sebenarnya di PromotionService sudah support mix reward dan type
     * (walaupun belum dicek sepenuhnya) next pengembangan lebih baik
     */
    public function defaultRewardType(): PromotionRewardType
    {
        return match ($this) {
            self::BundleFixedPrice => PromotionRewardType::FixedPrice,
            self::BuyXGetY => PromotionRewardType::FreeItem,
            self::FlashSalePrice => PromotionRewardType::FixedPrice,
            self::PercentDiscount => PromotionRewardType::PercentDiscount,
            self::FixedDiscount => PromotionRewardType::FixedDiscount,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::BundleFixedPrice => 'Beli Sekian Harga Pas',
            self::BuyXGetY => 'Beli X Dapat Y Gratis',
            self::FlashSalePrice => 'Harga Flash Sale',
            self::PercentDiscount => 'Diskon Persen',
            self::FixedDiscount => 'Diskon Nominal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BundleFixedPrice => 'warning',
            self::BuyXGetY => 'success',
            self::FlashSalePrice => 'danger',
            self::PercentDiscount => 'info',
            self::FixedDiscount => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::BundleFixedPrice => 'heroicon-o-shopping-bag',
            self::BuyXGetY => 'heroicon-o-gift',
            self::FlashSalePrice => 'heroicon-o-bolt',
            self::PercentDiscount => 'heroicon-o-percent-badge',
            self::FixedDiscount => 'heroicon-o-ticket',
        };
    }

    public function getDescriptionExample(): string
    {
        return match ($this) {
            self::BundleFixedPrice => 'Contoh: beli 3 Es Teh cukup bayar Rp 10.000. Pilih produk dan isi Jumlah Minimum = 3, lalu di bagian Hadiah pilih Harga Khusus dan isi Nilai = 10000. ',
            self::BuyXGetY => 'Contoh: beli 2 Es Teh gratis 1. Pilih produk dan isi Jumlah Minimum = 2, lalu di bagian Hadiah pilih Item Gratis dan isi Jumlah = 1. Produk gratis boleh dikosongkan agar otomatis mengikuti produk syarat.',
            self::FlashSalePrice => 'Contoh: Es Teh flash sale Rp 5.000 per cup di jam 7-9 pagi dan 10-12 malam. Pilih produk dan isi Jumlah Minimum = 1, lalu di bagian Hadiah pilih Harga Khusus dan isi Nilai = 5000. Lalu di bagian Waktu Promo pilih jam 7-9 pagi dan 10-12 malam. ',
            self::PercentDiscount => 'Contoh: diskon 20% untuk Es Teh. Pilih produk dan isi Jumlah Minimum = 1, lalu di bagian Hadiah pilih Diskon Persen dan isi Nilai = 20. ',
            self::FixedDiscount => 'Contoh: potongan Rp 2.000 untuk Es Teh. Pilih produk dan isi Jumlah Minimum = 1, lalu di bagian Hadiah pilih Diskon Nominal dan isi Nilai = 2000. ',
        };
    }

    public static function descriptionFor(self|string|null $type): string
    {
        if ($type instanceof self) {
            return $type->getDescriptionExample();
        }

        if (\is_string($type)) {
            $enum = self::tryFrom($type);

            if ($enum instanceof self) {
                return $enum->getDescriptionExample();
            }
        }

        return 'Pilih tipe promo untuk melihat contoh pengisian.';
    }
}
