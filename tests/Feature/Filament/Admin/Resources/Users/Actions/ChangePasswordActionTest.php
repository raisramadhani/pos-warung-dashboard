<?php

use App\Filament\Admin\Resources\Users\Actions\ChangePasswordAction;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can change password via action', function () {
    $user = User::factory()->create(['username' => 'validuser2']);
    $newPassword = 'new-secret-password';

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction('changePassword', data: [
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();
});

test('change password action has correct default name', function () {
    $reflection = new ReflectionClass(ChangePasswordAction::class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance->getDefaultName())->toBe('changePassword');
});

// ─── Sad Path ───────────────────────────────────────────

test('change password validates min length', function () {
    $user = User::factory()->create(['username' => 'pwminlen']);

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction('changePassword', [
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ])
        ->assertHasFormErrors(['new_password' => 'min']);
});

test('change password validates confirmation mismatch', function () {
    $user = User::factory()->create(['username' => 'pwmismatch']);

    $pw = 'longpassword';

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction('changePassword', [
            'new_password' => $pw,
            'new_password_confirmation' => 'differentpassword',
        ])
        ->assertNotified('Password Baru dan Konfirmasi Password Baru harus sama.');
});

test('change password validates required', function () {
    $user = User::factory()->create(['username' => 'pwrequired']);

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction('changePassword', [
            'new_password' => '',
            'new_password_confirmation' => '',
        ])
        ->assertHasFormErrors(['new_password' => 'required']);
});

// ─── Edge Cases ─────────────────────────────────────────

test('change password does not update when mismatch', function () {
    $user = User::factory()->create(['username' => 'pwnotupdate', 'password' => Hash::make('oldpass')]);

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction('changePassword', [
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'different',
        ])
        ->assertNotified('Password Baru dan Konfirmasi Password Baru harus sama.');

    expect(Hash::check('oldpass', $user->fresh()->password))->toBeTrue();
});
