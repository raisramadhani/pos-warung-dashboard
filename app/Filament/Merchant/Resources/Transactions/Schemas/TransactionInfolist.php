<?php

namespace App\Filament\Merchant\Resources\Transactions\Schemas;

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Promotions\PromotionRedemption;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Transaksi')
                    ->description('Detail transaksi penjualan')
                    ->schema([
                        TextEntry::make('transaction_number')
                            ->label('No. Transaksi'),
                        TextEntry::make('customer.name')
                            ->label('Pelanggan')
                            ->placeholder('-'),
                        TextEntry::make('payment_method')
                            ->label('Metode Bayar')
                            ->badge(),
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->numeric(),
                        TextEntry::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                        TextEntry::make('total_amount')
                            ->label('Grandtotal')
                            ->numeric()
                            ->weight(FontWeight::Bold),
                        TextEntry::make('amount_received')
                            ->label('Uang Diterima')
                            ->numeric(),
                        TextEntry::make('change')
                            ->label('Kembalian')
                            ->numeric()
                            ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                        TextEntry::make('notes')
                            ->label('Catatan'),
                        TextEntry::make('transaction_at')
                            ->label('Tanggal Transaksi')
                            ->dateTime(),
                    ])
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                Section::make('Promo')
                    ->description('Daftar promo yang diterapkan pada transaksi beserta syarat & hadiahnya')
                    ->visible(fn ($record): bool => $record !== null && $record->promotionRedemptions->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('promotionRedemptions')
                            ->label('Promo')
                            ->schema([
                                // Data promo diambil dari snapshot (transactionItem.promotion_data),
                                // bukan relasi live, agar tetap tercatat walau data promo diubah/dihapus.
                                TextEntry::make('transactionItem.promotion_data.name')
                                    ->label('Promo')
                                    ->placeholder('-'),
                                TextEntry::make('transactionItem.product_data.name')
                                    ->label('Produk')
                                    ->placeholder('-'),
                                TextEntry::make('transactionItem.promotion_data.type')
                                    ->label('Tipe')
                                    ->badge()
                                    ->formatStateUsing(fn (mixed $state): string => self::promotionTypeLabel($state)),
                                TextEntry::make('conditions_note')
                                    ->label('Syarat')
                                    ->state(fn ($record): string => self::conditionsNote($record))
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('rewards_note')
                                    ->label('Hadiah')
                                    ->state(fn ($record): string => self::rewardsNote($record))
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('discount_amount')
                                    ->label('Diskon')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state): string => format_quantity($state)),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    private static function promotionTypeLabel(mixed $state): string
    {
        $type = $state instanceof PromotionType ? $state : PromotionType::tryFrom((string) $state);

        return $type?->getLabel() ?? '-';
    }

    /**
     * Teks ringkas syarat promo per condition (mis. "Beli Es Teh Reguler minimal 2 item").
     * Dibaca dari snapshot transactionItem.promotion_data.conditions.
     */
    private static function conditionsNote(mixed $record): string
    {
        if (! $record instanceof PromotionRedemption) {
            return '';
        }

        $conditions = $record->transactionItem?->promotionData()['conditions'] ?? [];

        if (empty($conditions)) {
            return '';
        }

        return collect($conditions)
            ->map(function (array $condition): string {
                $target = $condition['product_name'] ?? $condition['category_name'] ?? '-';
                $minQuantity = format_quantity($condition['min_quantity'] ?? 0);

                return "Beli {$target} minimal {$minQuantity} item";
            })
            ->join('; ');
    }

    /**
     * Teks ringkas hadiah promo per reward, disesuaikan dengan jenis hadiahnya.
     * Dibaca dari snapshot transactionItem.promotion_data.rewards.
     */
    private static function rewardsNote(mixed $record): string
    {
        if (! $record instanceof PromotionRedemption) {
            return '';
        }

        $rewards = $record->transactionItem?->promotionData()['rewards'] ?? [];

        if (empty($rewards)) {
            return '';
        }

        return collect($rewards)
            ->map(function (array $reward): string {
                $rewardType = PromotionRewardType::tryFrom($reward['reward_type'] ?? '');

                return match ($rewardType) {
                    PromotionRewardType::FreeItem => 'Item Gratis: '.($reward['product_name'] ?? 'mengikuti produk syarat').' ('.format_quantity($reward['quantity'] ?? 0).' item)',
                    PromotionRewardType::FixedPrice => 'Harga Khusus: Rp '.format_quantity($reward['value'] ?? 0),
                    PromotionRewardType::PercentDiscount => 'Diskon '.format_quantity($reward['value'] ?? 0).'%',
                    PromotionRewardType::FixedDiscount => 'Diskon Rp '.format_quantity($reward['value'] ?? 0),
                    default => '',
                };
            })
            ->filter()
            ->join('; ');
    }
}
