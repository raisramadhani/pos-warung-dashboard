<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\Session;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class LogoutSessionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'logout';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Logout')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (Session $record): string => $record->isCurrent()
                ? 'Logout current session?'
                : 'Logout this session?')
            ->modalDescription(fn (Session $record): string => $record->isCurrent()
                ? 'You are about to terminate your own session. You will be logged out.'
                : 'This session will be terminated.')
            ->action(fn (Session $record) => $this->terminateSession($record));
    }

    private function terminateSession(Session $record): void
    {
        $isCurrent = $record->isCurrent();

        $record->delete();

        if ($isCurrent) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            redirect()->to('/admin/login')->send();
        }
    }
}
