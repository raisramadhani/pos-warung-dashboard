<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class LogoutAllSessionsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'logoutAllSessions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Logout All Sessions')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Logout all sessions?')
            ->modalDescription(function (): string {
                /** @var User $ownerRecord */
                $ownerRecord = $this->getLivewire()->getOwnerRecord();
                $isSelf = $ownerRecord->is(auth()->user());

                if ($isSelf) {
                    return 'All sessions will be terminated. You will be logged out as well.';
                }

                return 'All sessions for this user will be terminated.';
            })
            ->action(function ($livewire): void {
                /** @var User $owner */
                $owner = $livewire->getOwnerRecord();

                $isSelf = $owner->is(auth()->user());

                $owner->sessions()->delete();

                if ($isSelf) {
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    redirect()->to('/admin/login')->send();
                }
            });
    }
}
