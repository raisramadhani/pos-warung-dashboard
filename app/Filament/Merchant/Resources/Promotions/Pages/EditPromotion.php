<?php

namespace App\Filament\Merchant\Resources\Promotions\Pages;

use App\Filament\Merchant\Resources\Promotions\PromotionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected ?string $heading = 'Edit Promo';

    protected ?string $subheading = 'Ubah data promo';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $startsAt = $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;

        if ($startsAt === null && $endsAt === null) {
            $data['date_range'] = null;
        } else {
            $startCarbon = $startsAt !== null ? $this->toCarbon($startsAt) : null;
            $endCarbon = $endsAt !== null ? $this->toCarbon($endsAt) : $startCarbon;

            if ($startCarbon === null && $endCarbon === null) {
                $data['date_range'] = null;
            } elseif ($startCarbon !== null && $endCarbon !== null) {
                $data['date_range'] = $startCarbon->format('d/m/Y').' - '.$endCarbon->format('d/m/Y');
            } elseif ($startCarbon !== null) {
                $data['date_range'] = $startCarbon->format('d/m/Y').' - '.$startCarbon->format('d/m/Y');
            } else {
                $data['date_range'] = $endCarbon->format('d/m/Y').' - '.$endCarbon->format('d/m/Y');
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$startsAt, $endsAt] = $this->resolvePeriod($data['date_range'] ?? null);
        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $endsAt;
        unset($data['date_range']);

        return $data;
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (\is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolvePeriod(mixed $range): array
    {
        if ($range === null || $range === '') {
            return [null, null];
        }

        if (\is_array($range)) {
            $startRaw = trim((string) ($range[0] ?? ''));
            $endRaw = trim((string) ($range[1] ?? ''));

            if ($startRaw === '' && $endRaw === '') {
                return [null, null];
            }

            if ($startRaw === '') {
                $startRaw = $endRaw;
            }

            if ($endRaw === '') {
                $endRaw = $startRaw;
            }

            $joined = $startRaw.' - '.$endRaw;

            try {
                [$start, $end] = parse_period($joined);

                return [$start->startOfDay(), $end->endOfDay()];
            } catch (\Throwable) {
                return [$this->parseDateValue($startRaw)?->startOfDay(), $this->parseDateValue($endRaw)?->endOfDay()];
            }
        }

        if (\is_string($range)) {
            $trimmed = trim($range);

            if ($trimmed === '') {
                return [null, null];
            }

            try {
                [$start, $end] = parse_period($trimmed);

                return [$start->startOfDay(), $end->endOfDay()];
            } catch (\Throwable) {
                $parts = explode(' - ', $trimmed, 2);
                $start = isset($parts[0]) ? $this->parseDateValue(trim($parts[0])) : null;
                $end = isset($parts[1]) ? $this->parseDateValue(trim($parts[1])) : $start;

                return [$start?->startOfDay(), $end?->endOfDay()];
            }
        }

        return [null, null];
    }

    private function parseDateValue(string $raw): ?Carbon
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        try {
            return str_contains($raw, '/')
                ? Carbon::createFromFormat('d/m/Y', $raw)
                : Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
