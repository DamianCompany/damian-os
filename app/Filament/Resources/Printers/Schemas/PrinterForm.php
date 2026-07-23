<?php

namespace App\Filament\Resources\Printers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrinterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación de la impresora')
                    ->description('Registra únicamente cómo identificarla y dónde se encuentra.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej. Bambu Lab A1')
                            ->helperText('Usa un nombre corto y fácil de reconocer.')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('location')
                            ->label('Ubicación')
                            ->placeholder('Ej. Taller DAMI 3D · Mesa 1')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Disponibilidad')
                    ->description('Actualiza el estado solo cuando la impresora ya existe.')
                    ->schema([
                        Select::make('status')
                            ->label('Estado operativo')
                            ->options([
                                'available' => 'Disponible',
                                'in_use' => 'En uso',
                                'maintenance' => 'En mantenimiento',
                                'out_of_service' => 'Fuera de servicio',
                            ])
                            ->default('available')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Visible para asignaciones')
                            ->helperText('Desactívala si ya no debe aparecer en la operación.')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }
}
