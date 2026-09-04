<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

class ChangePasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'changePassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Ubah Password')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->modalHeading('Ubah Password')
            ->form([
                TextInput::make('new_password')
                    ->password()
                    ->label('Password Baru')
                    ->placeholder('Masukan password baru')
                    ->required()
                    ->minLength(8),
                TextInput::make('new_password_confirmation')
                    ->password()
                    ->label('Konfirmasi Password Baru')
                    ->placeholder('Masukkan ulang password baru')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                if ($data['new_password'] !== ($data['new_password_confirmation'] ?? null)) {
                    $this->failureNotificationTitle('Password Baru dan Konfirmasi Password Baru harus sama.');
                    $this->sendFailureNotification();

                    return;
                }

                $record->update(['password' => Hash::make($data['new_password'])]);
            })
            ->successNotificationTitle('Password berhasil diubah');
    }
}
