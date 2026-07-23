<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public function getTitle(): string|Htmlable
    {
        return 'Iniciar sesión | DAMIAN OS';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Bienvenido';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Ingresa tus credenciales para continuar';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->placeholder('tu@empresa.com')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->placeholder('Ingresa tu contraseña')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Recordarme');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Ingresar')
            ->submit('authenticate');
    }
}
