<?php

use Illuminate\Support\Carbon;

it('returns current server timestamp in milliseconds', function () {
    $before = Carbon::now()->valueOf();

    $response = $this->getJson('/api/server-time');

    $response->assertOk()
        ->assertJsonStructure(['timestamp']);

    $after = Carbon::now()->valueOf();
    $timestamp = $response->json('timestamp');

    expect($timestamp)
        ->toBeInt()
        ->toBeGreaterThanOrEqual($before)
        ->toBeLessThanOrEqual($after);
});

it('does not cache the server-time response', function () {
    $response = $this->getJson('/api/server-time');

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store');
});
