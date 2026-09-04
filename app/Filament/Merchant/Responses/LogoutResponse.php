<?php

namespace App\Filament\Merchant\Responses;

use Filament\Auth\Http\Responses\LogoutResponse as BaseLogoutResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutResponse extends BaseLogoutResponse
{
    public function toResponse(mixed $request): RedirectResponse|Redirector
    {
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }
}
