<?php

namespace App\Filament\Pages\Auth;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\RoleType;
use App\Models\Session;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class LoginPage extends Login
{
    protected ?string $heading = 'Masuk';

    public ?array $sessionNotification = null;

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    public function mount(): void
    {
        // Read session notification flash data before it's consumed
        $this->sessionNotification = session()->get('session_notification');

        if (Filament::auth()->check()) {
            $user = Auth::user();

            $shouldBlock = match (true) {
                $user->role === RoleType::SuperAdmin => false,
                default => $user->merchants()->where('current_status', MerchantStatus::Active)->doesntExist(),
            };

            if ($shouldBlock) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                $this->form->fill();

                return;
            }

            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        return null;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->hint(new HtmlString('You can use your username to login'))
            ->placeholder('Enter your username');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->hint(new HtmlString('Enter your account password'))
            ->password()
            ->placeholder('Enter your password')
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $result = parent::authenticate();

        $user = Auth::user();

        if (! $user) {
            return $result;
        }

        if ($user->role === RoleType::SuperAdmin) {
            return $result;
        }

        if ($user->role === RoleType::Merchant) { // @phpstan-ignore-line
            if ($user->merchants()->where('current_status', MerchantStatus::Active)->doesntExist()) {
                $message = $this->resolveResellerBlockMessage($user);

                Auth::logout();

                throw ValidationException::withMessages([
                    'data.username' => $message,
                ]);
            }

            // Invalidate all other sessions for this merchant user
            Session::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        return $result;
    }

    private function resolveResellerBlockMessage(User $user): string
    {
        $resellerCount = $user->merchants()->count();

        if ($resellerCount === 0) {
            return 'Anda tidak terdaftar sebagai mitra.';
        }

        $statuses = $user->merchants()->pluck('current_status')->unique();

        if ($statuses->contains(MerchantStatus::Inactive)) {
            return 'Akun Anda tidak aktif. Silakan hubungi administrator.';
        }

        return 'Akun Anda tidak aktif. Silakan hubungi administrator.';
    }

    public function getLayout(): string
    {
        return 'filament.merchant.pages.auth.login-page';
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }
}
