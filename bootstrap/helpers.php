<?php

use Illuminate\Support\Carbon;

if (! function_exists('to_number')) {
    /**
     * Convert a string to a number by removing non-numeric characters.
     *
     * Handles Indonesian (dot thousands, comma decimal) and Western
     * (comma thousands, dot decimal) formats:
     *   '10.000'  -> 10000
     *   '10,000'  -> 10000
     *   '10.5'    -> 10.5
     *   '10,5'    -> 10.5
     *   '2.500.000' -> 2500000
     *
     * @param  string|int|float|null  $value  The input value to convert.
     * @return float The converted number.
     */
    function to_number(string|int|float|null $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);
        $negative = str_starts_with($string, '-');

        if ($string === '' || $string === '-') {
            return 0;
        }

        // Keep only digits and separators.
        $cleaned = preg_replace('/[^0-9.,]/', '', $string) ?? '';

        $dots = substr_count($cleaned, '.');
        $commas = substr_count($cleaned, ',');

        // Both separators present -> the last one is decimal, the rest thousands.
        // '1.234,56' -> 1234.56 | '1,234.56' -> 1234.56
        if ($dots > 0 && $commas > 0) {
            $decimalSep = strrpos($cleaned, '.') > strrpos($cleaned, ',') ? '.' : ',';
            $thousandsSep = $decimalSep === '.' ? ',' : '.';
            $normalized = str_replace($thousandsSep, '', $cleaned);
            $normalized = str_replace($decimalSep, '.', $normalized);

            $number = (float) $normalized;

            return $negative ? -$number : $number;
        }

        // Exactly one separator type.
        if ($dots > 0 || $commas > 0) {
            $sep = $dots > 0 ? '.' : ',';
            $count = max($dots, $commas);

            if ($count > 1) {
                // Repeated separator -> thousands separator. '2.500.000' -> 2500000
                $number = (float) str_replace($sep, '', $cleaned);
            } elseif ($sep === '.' && strlen((explode('.', $cleaned))[1] ?? '') === 3) {
                // Single dot with 3 digits after -> thousands. '10.000' -> 10000
                $number = (float) str_replace('.', '', $cleaned);
            } elseif ($sep === ',' && strlen((explode(',', $cleaned))[1] ?? '') === 3) {
                // Single comma with 3 digits after -> thousands. '10,000' -> 10000
                $number = (float) str_replace(',', '', $cleaned);
            } else {
                // Otherwise it is a decimal separator. '10.5' -> 10.5 | '10,5' -> 10.5
                $number = (float) str_replace($sep, '.', $cleaned);
            }

            return $negative ? -$number : $number;
        }

        $number = (float) $cleaned;

        return $negative ? -$number : $number;
    }
}

if (! function_exists('format_rupiah')) {
    /**
     * Format a value as Indonesian Rupiah ("Rp" attached, per KBBI standard).
     *
     * The value is parsed with to_number() before formatting. A negative
     * decimals argument rounds the value to that many significant digits
     * (e.g. -2 rounds to hundreds) and renders it without decimal places.
     *
     * @param  string|int|float|null  $value  The input value to format.
     * @param  int  $decimals  Number of decimal places (default 0).
     * @return string The formatted rupiah string, e.g. "Rp1.500" or "-Rp1.500,50".
     */
    function format_rupiah(string|int|float|null $value, int $decimals = 0): string
    {
        $number = to_number($value);

        if ($decimals < 0) {
            $number = round($number, $decimals);
            $decimals = 0;
        }

        $formatted = number_format($number, $decimals, ',', '.');

        return $number < 0 ? '-Rp'.ltrim($formatted, '-') : 'Rp'.$formatted;
    }
}

if (! function_exists('format_quantity')) {
    /**
     * Format a stock quantity with up to 4 decimals, Indonesian style,
     * stripping trailing zeros: 10 -> "10", 0.5 -> "0,5", -0.0005 -> "-0,0005".
     *
     * @param  string|int|float|null  $value  The input value to format.
     */
    function format_quantity(string|int|float|null $value): string
    {
        $number = to_number($value);
        $formatted = rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');

        return $formatted === '-0' ? '0' : $formatted;
    }
}

if (! function_exists('parse_period')) {
    /**
     * Parse DateRangePicker period string ("d/m/Y - d/m/Y" or "Y-m-d - Y-m-d") into Carbon start/end.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    function parse_period(string $period): array
    {
        $parts = explode(' - ', $period);

        $parse = function (string $raw): Carbon {
            $raw = trim($raw);

            return str_contains($raw, '/')
                ? Carbon::createFromFormat('d/m/Y', $raw)
                : Carbon::parse($raw);
        };

        return [$parse($parts[0]), $parse($parts[1] ?? $parts[0])];
    }
}
