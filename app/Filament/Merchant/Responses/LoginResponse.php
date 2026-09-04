<?php

namespace App\Filament\Merchant\Responses;

use App\Enums\RoleType;
use Filament\Auth\Http\Responses\LoginResponse as BaseLoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends BaseLoginResponse
{
    public function toResponse(mixed $request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user?->role === RoleType::SuperAdmin) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('home'));
    }
}
