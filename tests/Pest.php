<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Shared Filament setup
|--------------------------------------------------------------------------
|
| Global beforeEach hooks for the Admin and Merchant Filament panels.
| These run BEFORE each file-level beforeEach (ChainableClosure in
| Testable::setUp binds global hook first, then file hook).
|
| Files that need a DIFFERENT merchant (e.g. ListStocksTest with
| 'Warung Utama') simply re-create $this->merchant in their file hook.
| Files that need a DIFFERENT user (e.g. Categories Pages using
| $user->merchants()->attach()) can re-create $this->user too.
*/

use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Facades\Filament;

pest()->beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
})->in('Feature/Filament/Admin');

pest()->beforeEach(function () {
    // Login/Response tests need guest state — skip shared auth.
    if (str_contains(static::class, 'LoginLogoutResponseTest')) {
        return;
    }

    $this->merchant = Merchant::factory()->active()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
    $this->actingAs($this->user);
    Filament::setCurrentPanel('merchant');
    Filament::setTenant($this->merchant);
})->in('Feature/Filament/Merchant');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
