<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de acceso')
                    ->description('Información que la persona utilizará para iniciar sesión.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->placeholder('Nombre y apellidos')
                            ->required(),
                        TextInput::make('email')
                            ->label('Correo de acceso')
                            ->placeholder('usuario@damiancompany.com.pe')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Contraseña temporal')
                            ->password()
                            ->revealable()
                            ->helperText('La persona podrá usarla para su primer acceso.')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state)),
                    ])
                    ->columns(2),
                Section::make('Permiso y vigencia')
                    ->description('Define qué podrá hacer esta credencial y hasta cuándo estará disponible.')
                    ->schema([
                        Select::make('role')
                            ->label('Perfil de acceso')
                            ->options([
                                'gerencia' => 'Gerencia',
                                'dami_3d' => 'Supervisor DAMI 3D',
                                'investiga_lab' => 'Supervisor InvestigaLab',
                                'automation' => 'Supervisor Damian Automation',
                            ])
                            ->default('dami_3d')
                            ->required(),
                        DateTimePicker::make('credential_expires_at')
                            ->label('Fecha de vencimiento')
                            ->helperText('Opcional. Déjalo vacío si el acceso no vence.')
                            ->native(false)
                            ->seconds(false),
                        Toggle::make('is_active')
                            ->label('Acceso habilitado')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }
}
