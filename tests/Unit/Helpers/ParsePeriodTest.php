<?php

use Illuminate\Support\Carbon;

test('parse_period parses Y-m-d format', function () {
    [$start, $end] = parse_period('2026-06-01 - 2026-06-30');

    expect($start)->toBeInstanceOf(Carbon::class)
        ->and($start->format('Y-m-d'))->toBe('2026-06-01')
        ->and($end->format('Y-m-d'))->toBe('2026-06-30');
});

test('parse_period parses d/m/Y format', function () {
    [$start, $end] = parse_period('01/06/2026 - 30/06/2026');

    expect($start)->toBeInstanceOf(Carbon::class)
        ->and($start->format('Y-m-d'))->toBe('2026-06-01')
        ->and($end->format('Y-m-d'))->toBe('2026-06-30');
});

test('parse_period handles single day range', function () {
    [$start, $end] = parse_period('2026-06-15 - 2026-06-15');

    expect($start->format('Y-m-d'))->toBe('2026-06-15')
        ->and($end->format('Y-m-d'))->toBe('2026-06-15')
        ->and($start->isSameDay($end))->toBeTrue();
});

test('parse_period defaults end to start when only one date provided', function () {
    [$start, $end] = parse_period('2026-06-15');

    expect($start->format('Y-m-d'))->toBe('2026-06-15')
        ->and($end->format('Y-m-d'))->toBe('2026-06-15');
});

test('parse_period trims whitespace around dates', function () {
    [$start, $end] = parse_period(' 2026-06-01  -  2026-06-30 ');

    expect($start->format('Y-m-d'))->toBe('2026-06-01')
        ->and($end->format('Y-m-d'))->toBe('2026-06-30');
});

test('parse_period returns array with two Carbon instances', function () {
    $result = parse_period('2026-01-01 - 2026-12-31');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Carbon::class)
        ->and($result[1])->toBeInstanceOf(Carbon::class);
});

test('parse_period preserves time as start of day', function () {
    [$start, $end] = parse_period('2026-06-01 - 2026-06-30');

    expect($start->hour)->toBe(0)
        ->and($start->minute)->toBe(0)
        ->and($end->hour)->toBe(0)
        ->and($end->minute)->toBe(0);
});

test('parse_period handles d/m/Y single day', function () {
    [$start, $end] = parse_period('15/06/2026');

    expect($start->format('Y-m-d'))->toBe('2026-06-15')
        ->and($end->format('Y-m-d'))->toBe('2026-06-15');
});
