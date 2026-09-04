<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil berhasil diperbarui';
    }

    protected function getRedirectUrl(): ?string
    {
        return route('home');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function hasLogo(): bool
    {
        return true;
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Lengkap')
            ->required()
            ->placeholder('Masukan nama lengkap Anda')
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->email()
            ->nullable()
            ->maxLength(255)
            ->placeholder('Masukan alamat email Anda')
            ->unique('users', ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi Baru')
            ->validationAttribute('Kata Sandi Baru')
            ->password()
            ->placeholder('Biarkan kosong jika tidak ingin mengubah kata sandi')
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (?string $state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Kata Sandi')
            ->validationAttribute('Konfirmasi Kata Sandi')
            ->password()
            ->autocomplete('new-password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->placeholder('Masukkan ulang kata sandi baru Anda')
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label('Kata Sandi Saat Ini')
            ->validationAttribute('Kata Sandi Saat Ini')
            ->belowContent('Masukkan kata sandi saat ini Anda')
            ->password()
            ->placeholder('Masukkan kata sandi saat ini Anda')
            ->autocomplete('current-password')
            ->currentPassword(guard: Filament::getAuthGuard())
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->description('Perbarui informasi profil dan alamat email akun Anda.')
                    ->columns(1)
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Foto Profil')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->nullable()
                            ->helperText('Format gambar (JPG/PNG/WebP). Maksimal 1 file.')
                            ->alignCenter(),
                        $this->getNameFormComponent(),
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique('users', ignoreRecord: true)
                            ->placeholder('Masukan username baru anda')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('No. Handphone')
                            ->placeholder('Masukan no. handphone Anda')
                            ->maxLength(20),
                        TextInput::make('address')
                            ->label('Alamat')
                            ->placeholder('Masukan alamat Anda')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ]),
            ]);
    }
}
