<?php

test('to_number converts plain numeric string to float', function () {
    expect(to_number('1500'))->toBe(1500.0);
});

test('to_number converts string with comma decimal separator to float', function () {
    expect(to_number('1500,50'))->toBe(1500.5);
});

test('to_number converts string with dot thousand separator to float', function () {
    expect(to_number('1.500'))->toBe(1500.0);
});

test('to_number converts string with both dot and comma separators', function () {
    expect(to_number('1.500,50'))->toBe(1500.5);
});

test('to_number returns 0 for null input', function () {
    expect(to_number(null))->toBe(0.0);
});

test('to_number returns 0 for empty string', function () {
    expect(to_number(''))->toBe(0.0);
});

test('to_number returns 0 for string with no digits', function () {
    expect(to_number('abc'))->toBe(0.0);
});

test('to_number handles rp prefix', function () {
    expect(to_number('Rp 2.500.000,00'))->toBe(2500000.0);
});

test('to_number strips non-numeric characters', function () {
    expect(to_number('abc123def456'))->toBe(123456.0);
});

test('to_number passes through integer input', function () {
    expect(to_number(1500))->toBe(1500.0);
});

test('to_number passes through float input', function () {
    expect(to_number(1500.50))->toBe(1500.5);
});

test('to_number passes through zero integer', function () {
    expect(to_number(0))->toBe(0.0);
});

test('to_number converts negative plain numeric string', function () {
    expect(to_number('-1500'))->toBe(-1500.0);
});

test('to_number converts negative string with separators', function () {
    expect(to_number('-1.500,50'))->toBe(-1500.5);
});

test('to_number handles rp prefix with negative sign', function () {
    expect(to_number('-Rp 2.500.000,00'))->toBe(-2500000.0);
});

test('to_number returns 0 for bare minus sign', function () {
    expect(to_number('-'))->toBe(0.0);
});

test('to_number passes through negative integer', function () {
    expect(to_number(-1500))->toBe(-1500.0);
});

test('to_number passes through negative float', function () {
    expect(to_number(-1500.50))->toBe(-1500.5);
});
