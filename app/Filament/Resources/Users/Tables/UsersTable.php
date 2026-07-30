<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Persona')
                    ->description(fn ($record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user-circle'),
                TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'gerencia' => 'Gerencia',
                        'dami_3d' => 'Supervisor DAMI 3D',
                        'investiga_lab' => 'Supervisor InvestigaLab',
                        'automation' => 'Supervisor Damian Automation',
                        'servicio_tecnico' => 'Supervisor Servicio Técnico',
                        default => 'Perfil no reconocido',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'gerencia' => 'info',
                        'dami_3d', 'investiga_lab', 'automation', 'servicio_tecnico' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label('Acceso')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Habilitado' : 'Deshabilitado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('credential_expires_at')
                    ->label('Vigencia')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sin vencimiento'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        'gerencia' => 'Gerencia',
                        'dami_3d' => 'Supervisor DAMI 3D',
                        'investiga_lab' => 'Supervisor InvestigaLab',
                        'automation' => 'Supervisor Damian Automation',
                        'servicio_tecnico' => 'Supervisor Servicio Técnico',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Estado del acceso')
                    ->trueLabel('Habilitados')
                    ->falseLabel('Deshabilitados'),
            ])
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateHeading('Aún no hay credenciales')
            ->emptyStateDescription('Crea un acceso y asigna el perfil que corresponda.')
            ->recordActions([
                EditAction::make()->label('Editar acceso'),
            ]);
    }
}
