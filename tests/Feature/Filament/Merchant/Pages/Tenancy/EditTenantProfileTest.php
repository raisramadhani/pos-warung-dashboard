<?php

use App\Filament\Merchant\Pages\Tenancy\EditTenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->merchants()->attach($this->merchant);
    $this->actingAs($user);
});

// ─── Happy Path ───────────────────────────────────────────────

describe('happy path', function () {
    it('renders the tenant profile page with all fields', function () {
        livewire(EditTenantProfile::class)
            ->assertSuccessful()
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('avatar_path')
            ->assertFormFieldExists('address');
    });

    it('updates the tenant profile and redirects to home', function () {
        livewire(EditTenantProfile::class)
            ->fillForm([
                'name' => 'Toko Baru',
                'address' => 'Jl. Melati No. 12',
            ])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect(route('home'));

        assertDatabaseHas('merchants', [
            'id' => $this->merchant->id,
            'name' => 'Toko Baru',
            'address' => 'Jl. Melati No. 12',
        ]);
    });

    it('uploads an avatar image for the tenant', function () {
        $file = UploadedFile::fake()->image('logo.png');

        livewire(EditTenantProfile::class)
            ->fillForm([
                'name' => 'Toko Baru',
                'avatar_path' => $file,
            ])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->merchant->refresh();

        expect($this->merchant->avatar_path)->not->toBeNull();
        Storage::disk('public')->assertExists($this->merchant->avatar_path);
    });
});

// ─── Sad Path ─────────────────────────────────────────────────

describe('sad path', function () {
    it('requires a merchant name', function () {
        livewire(EditTenantProfile::class)
            ->fillForm(['name' => null])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    });

    it('rejects a name longer than 255 characters', function () {
        livewire(EditTenantProfile::class)
            ->fillForm(['name' => str_repeat('a', 256)])
            ->call('save')
            ->assertHasFormErrors(['name' => 'max:255']);
    });
});

// ─── Edge Cases ───────────────────────────────────────────────

describe('edge cases', function () {
    it('saves when optional fields are left empty', function () {
        livewire(EditTenantProfile::class)
            ->fillForm([
                'name' => 'Toko Baru',
                'address' => null,
            ])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        assertDatabaseHas('merchants', [
            'id' => $this->merchant->id,
            'name' => 'Toko Baru',
            'address' => null,
            'avatar_path' => null,
        ]);
    });

    it('accepts a name with exactly 255 characters', function () {
        $name = str_repeat('a', 255);

        livewire(EditTenantProfile::class)
            ->fillForm(['name' => $name])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        assertDatabaseHas('merchants', [
            'id' => $this->merchant->id,
            'name' => $name,
        ]);
    });

    it('saving without changes keeps the existing tenant data', function () {
        $original = $this->merchant->only(['name', 'address']);

        livewire(EditTenantProfile::class)
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->merchant->refresh();

        expect($this->merchant->only(['name', 'address']))
            ->toBe($original);
    });

    it('exposes the tenant profile label', function () {
        expect(EditTenantProfile::getLabel())->toBe('Profil Toko');
    });

    it('uses a custom saved notification title', function () {
        $component = livewire(EditTenantProfile::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getSavedNotificationTitle');
        $method->setAccessible(true);

        expect($method->invoke($component->instance()))->toBe('Profil toko berhasil diperbarui');
    });

    it('redirects to home after save', function () {
        $component = livewire(EditTenantProfile::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getRedirectUrl');
        $method->setAccessible(true);

        expect($method->invoke($component->instance()))->toBe(route('home'));
    });
});
