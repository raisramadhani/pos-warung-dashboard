<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Aktivitas')
                    ->schema([
                        TextEntry::make('log_name')->label('Log')->badge(),
                        TextEntry::make('event')->label('Event')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                'restored' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('description')->label('Deskripsi'),
                        TextEntry::make('subject_type')
                            ->label('Subject')
                            ->formatStateUsing(fn ($state, $record) => class_basename($state).' #'.$record->subject_id),
                        TextEntry::make('causer.name')->label('Oleh'),
                        TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s'),
                    ])->columns(3),

                Section::make('Properties')
                    ->schema([
                        KeyValueEntry::make('attribute_changes.old')
                            ->label('Data lama')
                            ->visible(fn ($record) => isset($record->attribute_changes['old'])),
                        KeyValueEntry::make('attribute_changes.attributes')
                            ->label('Data baru')
                            ->visible(fn ($record) => isset($record->attribute_changes['attributes'])),
                        KeyValueEntry::make('properties')
                            ->label('Lainnya')
                            ->visible(fn ($record) => ! empty($record->properties)),
                    ])->columns(2),
            ]);
    }
}
