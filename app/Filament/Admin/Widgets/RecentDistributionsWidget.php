<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Inventories\Distribution;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDistributionsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Distribusi Terbaru';
    }

    protected function getTableQuery(): Builder
    {
        return Distribution::query()
            ->with('merchant')
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('merchant.name')
                ->label('Cabang'),
            TextColumn::make('status')
                ->label('Status')
                ->badge(),
            TextColumn::make('items_count')
                ->label('Item')
                ->counts('items'),
            TextColumn::make('sent_at')
                ->label('Dikirim')
                ->dateTime(),
            TextColumn::make('received_at')
                ->label('Diterima')
                ->dateTime(),
        ];
    }
}
