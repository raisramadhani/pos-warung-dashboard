<?php

namespace App\Filament\Merchant\Resources\Promotions\Pages;

use App\Filament\Merchant\Resources\Promotions\PromotionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;

    protected ?string $heading = 'Tambah Promo';

    protected ?string $subheading = 'Buat promo baru';

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();

        if ($tenant !== null) {
            $data['merchant_id'] = $tenant->getKey();
        }

        $data['is_active'] = false;

        [$startsAt, $endsAt] = $this->resolvePeriod($data['date_range'] ?? null);
        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $endsAt;
        unset($data['date_range']);

        return $data;
    }

    /**
     * Resolve DateRangePicker value (string "d/m/Y - d/m/Y", array, or null) into [starts_at, ends_at].
     *
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
