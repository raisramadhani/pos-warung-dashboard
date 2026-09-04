<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MerchantsRelationManager extends RelationManager
{
    protected static string $relationship = 'merchants';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Merchant';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Merchant / Outlet')
            ->description('Daftar merchant tempat user terdaftar')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10)
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('current_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label('Member')
                    ->counts('members')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Status')
                    ->options(MerchantStatus::class)
                    ->placeholder('Pilih status'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'slug'])
                    ->multiple(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn ($record) => MerchantResource::getUrl('view', ['record' => $record])),
                    DetachAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
