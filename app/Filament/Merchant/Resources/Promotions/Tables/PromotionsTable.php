<?php

namespace App\Filament\Merchant\Resources\Promotions\Tables;

use App\Enums\Promotions\PromotionType;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Promo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('product_name')
                    ->label('Produk')
                    ->state(fn ($record) => $record->conditions()->with('product')->first()?->product->name ?? '-')
                    ->searchable(['conditions.product.name'])
                    ->sortable(false),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('gray')
                    ->updateStateUsing(function (Promotion $record, bool $state): bool {
                        if ($state) {
                            $conflictedNames = self::conflictedProductNames($record);

                            if ($conflictedNames !== []) {
                                Notification::make()
                                    ->title('Aktivasi promo ditolak')
                                    ->body('Produk ini sudah dipakai promo aktif lain: '.implode(', ', $conflictedNames))
                                    ->danger()
                                    ->send();

                                return false;
                            }
                        }

                        $record->update([
                            'is_active' => $state,
                        ]);

                        Notification::make()
                            ->title($state ? 'Promo berhasil diaktifkan' : 'Promo dinonaktifkan')
                            ->body($state
                                ? 'Promo sekarang aktif dan bisa dipakai pada transaksi POS.'
                                : 'Promo tidak aktif dan tidak akan dipakai pada transaksi POS.')
                            ->success()
                            ->send();

                        return $state;
                    })
                    ->sortable(),
                TextColumn::make('schedules_count')
                    ->label('Hari')
                    ->counts('schedules')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('redemptions_count')
                    ->label('Pemakaian')
                    ->counts('redemptions')
                    ->numeric()
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(PromotionType::class),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function conflictedProductNames(Promotion $record): array
    {
        $conditionProductIds = $record->conditions()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($conditionProductIds === []) {
            return [];
        }

        // Abaikan produk yang sudah soft delete dari validasi konflik aktivasi.
        $productIds = Product::query()
            ->whereIn('id', $conditionProductIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($productIds === []) {
            return [];
        }

        return Product::query()
            ->where('merchant_id', $record->merchant_id)
            ->whereIn('id', $productIds)
            ->whereIn('id', function ($query) use ($record): void {
                $query->select('promotion_conditions.product_id')
                    ->from('promotion_conditions')
                    ->join('promotions', 'promotions.id', '=', 'promotion_conditions.promotion_id')
                    ->where('promotions.merchant_id', $record->merchant_id)
                    ->where('promotions.is_active', true)
                    ->whereNull('promotions.deleted_at')
                    ->where('promotions.id', '!=', $record->id);
            })
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
