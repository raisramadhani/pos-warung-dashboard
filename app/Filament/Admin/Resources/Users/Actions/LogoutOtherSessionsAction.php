<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;

class LogoutOtherSessionsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'logoutOtherSessions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Logout Other Sessions')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Logout other sessions?')
            ->modalDescription('All sessions except your current one will be terminated.')
            ->action(function ($livewire): void {
                /** @var User $owner */
                $owner = $livewire->getOwnerRecord();

                $owner->sessions()
                    ->where('id', '!=', session()->getId())
                    ->delete();
            });
    }
}
