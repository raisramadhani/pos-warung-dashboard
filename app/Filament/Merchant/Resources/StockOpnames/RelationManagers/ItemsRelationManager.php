<?php

namespace App\Filament\Merchant\Resources\StockOpnames\RelationManagers;

use App\Enums\Inventories\StockOpnameStatus;
use App\Enums\Inventories\StockOpnameType;
use App\Filament\Exports\StockOpnameItemExporter;
use App\Filament\Merchant\Resources\StockOpnames\Actions\AddItemAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\CancelAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\CompleteAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\DeleteOpnameItemAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\SetEquivalenAction;
use App\Models\Inventories\StockOpnameItem;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'item.name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Item';
    }

    protected function getOwnerStatus(): StockOpnameStatus
    {
        $status = $this->getOwnerRecord()->getAttribute('status');

        return $status instanceof StockOpnameStatus ? $status : StockOpnameStatus::Draft;
    }

    public function table(Table $table): Table
    {
        $currentStatus = $this->getOwnerStatus();

        return $table
            ->heading('Item Barang')
            ->description('Daftar item yang dihitung dalam stock opname ini')
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10)
                    ->visibleFrom('md'),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('system_quantity')
                    ->label('Stok Sistem')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(function (StockOpnameItem $record): string {
                        return ' '.($record->item->unit ?? '');
                    }),
                $currentStatus === StockOpnameStatus::Counting
                ? TextInputColumn::make('actual_quantity')
                    ->label('Stok Fisik')
                    ->placeholder('Stok fisik')
                    ->rules(['required', 'min:0'])
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state !== null && $state !== '') {
                            $state = to_number($state);
                            $record->update([
                                'actual_quantity' => $state,
                                'difference' => $state - $record->system_quantity,
                            ]);
                        } else {
                            $record->update([
                                'actual_quantity' => null,
                                'difference' => null,
                            ]);
                        }
                    })
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                    ->state(function (StockOpnameItem $record) {
                        return $record->actual_quantity !== null ? format_quantity($record->actual_quantity) : null;
                    })
                    ->suffix(function (StockOpnameItem $record): string {
                        return ' '.($record->item->unit ?? '');
                    })
                    ->sortable()
                : TextColumn::make('actual_quantity')
                    ->label('Stok Fisik')
                    ->placeholder('- ')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(function (StockOpnameItem $record): string {
                        return ' '.($record->item->unit ?? '');
                    }),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->placeholder('- ')
                    ->color(fn (mixed $state): string => match (true) {
                        (int) ($state ?? 0) < 0 => 'danger',
                        (int) ($state ?? 0) > 0 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(function (StockOpnameItem $record): string {
                        return ' '.($record->item->unit ?? '');
                    }),
                $currentStatus === StockOpnameStatus::Counting
                ? SelectColumn::make('action_type')
                    ->label('Jenis Opname')
                    ->native(false)
                    ->options(StockOpnameType::class)
                    ->placeholder('-')
                    ->default(StockOpnameType::Adjustment)
                : TextColumn::make('action_type')
                    ->label('Jenis Opname')
                    ->badge()
                    ->placeholder('-')
                    ->formatStateUsing(fn (?StockOpnameType $state): string => $state?->getLabel() ?? '-'),
            ])
            ->filters([])
            ->headerActions([
                CompleteAction::make()
                    ->record($this->getOwnerRecord()),
                CancelAction::make()
                    ->record($this->getOwnerRecord()),
                AddItemAction::make(),
                ExportAction::make()
                    ->exporter(StockOpnameItemExporter::class)
                    ->columnMapping(false),
            ])
            ->actions([
                DeleteOpnameItemAction::make(),
            ])
            ->bulkActions([
                SetEquivalenAction::make(),
                ExportBulkAction::make()
                    ->exporter(StockOpnameItemExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
