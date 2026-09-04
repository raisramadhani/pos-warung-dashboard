<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Livewire\Component;

class AddItemAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'addItem';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tambah Item')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->form([
                Select::make('item_id')
                    ->label('Item')
                    ->relationship(
                        name: 'item',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query, $livewire) {
                            /** @var StockOpname $record */
                            $record = $livewire->getOwnerRecord();
                            $existingItemIds = $record->items()->pluck('item_id')->toArray();

                            return $query->whereNotIn('id', $existingItemIds);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data, $livewire) {
                /** @var StockOpname $record */
                $record = $livewire->getOwnerRecord();
                $systemQty = MerchantStock::query()->where('merchant_id', $record->merchant_id)
                    ->where('item_id', $data['item_id'])
                    ->value('quantity') ?? 0;

                $record->items()->create([
                    'item_id' => $data['item_id'],
                    'system_quantity' => $systemQty,
                ]);

                Notification::make()
                    ->title('Item ditambahkan')
                    ->success()
                    ->send();
            })
            ->visible(fn ($livewire) => $livewire->getOwnerRecord()->status === StockOpnameStatus::Counting)
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
