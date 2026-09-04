<?php

test('format_rupiah formats integer input', function () {
    expect(format_rupiah(1500))->toBe('Rp1.500');
});

test('format_rupiah formats float input', function () {
    expect(format_rupiah(1500.0))->toBe('Rp1.500');
});

test('format_rupiah formats plain numeric string', function () {
    expect(format_rupiah('1500'))->toBe('Rp1.500');
});

test('format_rupiah formats string with dot thousand separator', function () {
    expect(format_rupiah('1.500'))->toBe('Rp1.500');
});

test('format_rupiah formats string with comma decimal separator', function () {
    expect(format_rupiah('1500,50'))->toBe('Rp1.501');
});

test('format_rupiah formats with decimals', function () {
    expect(format_rupiah(1500.5, 2))->toBe('Rp1.500,50');
});

test('format_rupiah parses rp prefix from input', function () {
    expect(format_rupiah('Rp 2.500.000,00'))->toBe('Rp2.500.000');
});

test('format_rupiah returns Rp0 for null input', function () {
    expect(format_rupiah(null))->toBe('Rp0');
});

test('format_rupiah returns Rp0 for empty string', function () {
    expect(format_rupiah(''))->toBe('Rp0');
});

test('format_rupiah returns Rp0 for string with no digits', function () {
    expect(format_rupiah('abc'))->toBe('Rp0');
});

test('format_rupiah formats negative integer with minus prefix', function () {
    expect(format_rupiah(-1500))->toBe('-Rp1.500');
});

test('format_rupiah formats negative string input', function () {
    expect(format_rupiah('-1500'))->toBe('-Rp1.500');
});

test('format_rupiah formats negative value with decimals', function () {
    expect(format_rupiah('-1.500,50', 2))->toBe('-Rp1.500,50');
});

test('format_rupiah rounds with negative decimals without error', function () {
    expect(format_rupiah(1234.567, -1))->toBe('Rp1.230');
});

test('format_rupiah rounds negative value with negative decimals', function () {
    expect(format_rupiah(-1234.567, -1))->toBe('-Rp1.230');
});

test('format_rupiah rounds hundreds with negative decimals', function () {
    expect(format_rupiah(1234.567, -2))->toBe('Rp1.200');
});

test('format_rupiah returns Rp0 for zero input', function () {
    expect(format_rupiah(0))->toBe('Rp0');
});

test('format_rupiah returns Rp0 for negative zero input', function () {
    expect(format_rupiah(-0.0))->toBe('Rp0');
});
