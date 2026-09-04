<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('public');

    $this->user = User::factory()->superAdmin()->create();
    $this->actingAs($this->user);

    // Bersihkan rate limiter save() per user agar test tidak ter-throttle.
    RateLimiter::clear('filament-edit-profile:'.$this->user->id);
});

// ─── Happy Path ───────────────────────────────────────────────

describe('happy path', function () {
    it('renders the edit profile page', function () {
        livewire(EditProfile::class)
            ->assertSuccessful()
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('phone')
            ->assertFormFieldExists('address')
            ->assertFormFieldExists('avatar_path');
    });

    it('saves profile data and redirects to home', function () {
        livewire(EditProfile::class)
            ->fillForm([
                'name' => 'Jane Doe',
                'username' => 'jane_doe',
                'email' => 'jane@example.com',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1',
            ])
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect(route('home'));

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Jane Doe',
            'username' => 'jane_doe',
            'email' => 'jane@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ]);
    });

    it('saves profile data without changing the email', function () {
        livewire(EditProfile::class)
            ->fillForm([
                'name' => 'Jane Doe',
                'username' => 'jane_doe',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1',
            ])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect(route('home'));

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Jane Doe',
            'username' => 'jane_doe',
            'email' => $this->user->email,
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ]);
    });

    it('changes password when current password is valid', function () {
        livewire(EditProfile::class)
            ->set('data.password', 'new-secret-password')
            ->set('data.passwordConfirmation', 'new-secret-password')
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->user->refresh();

        expect(Hash::check('new-secret-password', $this->user->password))->toBeTrue();
    });
});

// ─── Sad Path ─────────────────────────────────────────────────

describe('sad path', function () {
    it('rejects an invalid email address', function () {
        livewire(EditProfile::class)
            ->fillForm(['email' => 'not-an-email'])
            ->call('save')
            ->assertHasFormErrors(['email' => 'email']);
    });

    it('rejects a duplicate username', function () {
        User::factory()->create(['username' => 'taken_username']);

        livewire(EditProfile::class)
            ->fillForm(['username' => 'taken_username'])
            ->call('save')
            ->assertHasFormErrors(['username' => 'unique']);
    });

    it('rejects a missing username', function () {
        livewire(EditProfile::class)
            ->fillForm(['username' => null])
            ->call('save')
            ->assertHasFormErrors(['username' => 'required']);
    });

    it('rejects mismatched password confirmation', function () {
        livewire(EditProfile::class)
            ->set('data.password', 'new-secret-password')
            ->set('data.passwordConfirmation', 'different-password')
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertHasFormErrors(['password' => 'same']);
    });

    it('rejects an invalid current password', function () {
        livewire(EditProfile::class)
            ->set('data.password', 'new-secret-password')
            ->set('data.passwordConfirmation', 'new-secret-password')
            ->set('data.currentPassword', 'wrong-password')
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);
    });
});

// ─── Edge Cases ───────────────────────────────────────────────

describe('edge cases', function () {
    it('allows an empty email since the column is nullable', function () {
        livewire(EditProfile::class)
            ->fillForm([
                'name' => 'Jane Doe',
                'username' => 'jane_doe',
                'email' => null,
                'phone' => '081234567890',
            ])
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => null,
        ]);
    });

    it('keeps the existing password when the password field is left empty', function () {
        $originalPassword = $this->user->password;

        livewire(EditProfile::class)
            ->fillForm(['name' => 'Jane Doe'])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->user->refresh();

        expect($this->user->password)->toBe($originalPassword);
    });

    it('does not require the current password when nothing sensitive changes', function () {
        livewire(EditProfile::class)
            ->fillForm(['name' => 'Jane Doe'])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertHasNoFormErrors(['currentPassword']);

        $this->user->refresh();

        expect($this->user->name)->toBe('Jane Doe');
    });

    it('keeps the existing avatar when the avatar field is left empty', function () {
        Storage::disk('public')->put('avatars/old-avatar.png', 'avatar-bytes');

        $this->user->update(['avatar_path' => 'avatars/old-avatar.png']);

        livewire(EditProfile::class)
            ->fillForm(['name' => 'Jane Doe'])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->user->refresh();

        expect($this->user->avatar_path)->toBe('avatars/old-avatar.png')
            ->and(Storage::disk('public')->exists('avatars/old-avatar.png'))->toBeTrue();
    });

    it('uploads an avatar to the public disk', function () {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        livewire(EditProfile::class)
            ->fillForm(['avatar_path' => $avatar])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->user->refresh();

        expect($this->user->avatar_path)->not->toBeNull()
            ->and(Storage::disk('public')->exists($this->user->avatar_path))->toBeTrue()
            ->and(Storage::disk('public')->path($this->user->avatar_path))->toContain('avatars');
    });

    it('stores a newly uploaded avatar file on the public disk', function () {
        $avatar = UploadedFile::fake()->image('new-avatar.jpg', 100, 100);

        // User mengunggah file baru; FileUpload menyimpan file ke disk public.
        livewire(EditProfile::class)
            ->set('data.avatar_path', $avatar)
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->user->refresh();

        // Path lama tetap dipertahankan FileUpload (default tidak menghapus file lama),
        // tetapi file baru tersimpan di disk public.
        expect(Storage::disk('public')->allFiles())->not->toBeEmpty()
            ->and(collect(Storage::disk('public')->allFiles())
                ->contains(fn (string $file): bool => str_ends_with($file, '.jpg')))->toBeTrue();
    });
});
