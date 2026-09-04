<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('attribute_changes')
                    ->label('Perubahan Data')
                    ->state(function (Activity $record): string {
                        $changes = $record->attribute_changes ?? [];
                        $event = $record->event;

                        if (empty($changes)) {
                            return $record->description ?: '-';
                        }

                        if ($event === 'updated' && isset($changes['attributes'])) {
                            $parts = [];
                            foreach ($changes['attributes'] as $key => $newVal) {
                                $oldVal = $changes['old'][$key] ?? null;
                                $oldStr = \is_bool($oldVal) ? ($oldVal ? 'true' : 'false') : (\is_array($oldVal) ? json_encode($oldVal) : ($oldVal ?? 'null'));
                                $newStr = \is_bool($newVal) ? ($newVal ? 'true' : 'false') : (\is_array($newVal) ? json_encode($newVal) : ($newVal ?? 'null'));
                                $parts[] = "{$key}: {$oldStr} → {$newStr}";
                            }

                            return implode(', ', $parts);
                        }

                        if (isset($changes['attributes']) && \is_array($changes['attributes'])) {
                            $parts = [];
                            foreach ($changes['attributes'] as $key => $val) {
                                $valStr = \is_bool($val) ? ($val ? 'true' : 'false') : (\is_array($val) ? json_encode($val) : ($val ?? 'null'));
                                $parts[] = "{$key}: {$valStr}";
                            }

                            return implode(', ', $parts);
                        }

                        return $record->description ?: '-';
                    })
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (\strlen($state ?? '') <= 60) {
                            return null;
                        }

                        return $state;
                    }),
                TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label('Model ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('Oleh')
                    ->searchable(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Rentang Waktu')
                    ->placeholder('Pilih rentang tanggal'),

                SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options(
                        fn () => Activity::query()
                            ->whereNotNull('subject_type')
                            ->select('subject_type')
                            ->distinct()
                            ->pluck('subject_type')
                            ->mapWithKeys(fn (string $type) => [$type => class_basename($type)])
                            ->sort()
                            ->toArray()
                    )
                    ->searchable()
                    ->placeholder('Pilih Model'),

                Filter::make('causer_name')
                    ->label('Cari Oleh (User)')
                    ->schema([
                        TextInput::make('causer_name')
                            ->label('Nama User')
                            ->placeholder('Ketik nama user...'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['causer_name'] ?? null,
                        fn (Builder $q, string $name) => $q->whereHasMorph(
                            'causer',
                            '*',
                            fn (Builder $subQ) => $subQ->where('name', 'like', "%{$name}%")
                        )
                    )),

                Filter::make('subject_id')
                    ->label('Cari Model ID')
                    ->schema([
                        TextInput::make('subject_id')
                            ->label('Model ID')
                            ->numeric()
                            ->placeholder('Ketik ID...'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['subject_id'] ?? null,
                        fn (Builder $q, $id) => $q->where('subject_id', $id)
                    )),
            ])
            ->actions([
                // ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
