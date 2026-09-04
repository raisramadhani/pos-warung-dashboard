<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Users\Actions\LogoutAllSessionsAction;
use App\Filament\Admin\Resources\Users\Actions\LogoutOtherSessionsAction;
use App\Filament\Admin\Resources\Users\Actions\LogoutSessionAction;
use App\Models\Session;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Sessions';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sesi')
            ->description('Daftar sesi login pengguna')
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Session')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 16).'...')
                    ->copyable()
                    ->copyMessage('Session ID copied'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn (Session $record): ?string => $record->user_agent),
                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean()
                    ->state(fn (Session $record): bool => $record->isCurrent())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->defaultSort('last_activity', 'desc')
            ->filters([])
            ->headerActions([
                LogoutOtherSessionsAction::make(),
                LogoutAllSessionsAction::make(),
            ])
            ->actions([
                LogoutSessionAction::make(),
            ])
            ->bulkActions([
                ActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Logout selected sessions')
                        ->modalHeading('Logout selected sessions?')
                        ->modalDescription('Selected sessions will be terminated.'),
                ]),
            ]);
    }
}
