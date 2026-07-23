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
                    ->formatStateUsing(fn ($state) => $state === 'gerencia' ? 'Gerencia' : 'Supervisor DAMI 3D')
                    ->color(fn ($state) => $state === 'gerencia' ? 'info' : 'success'),
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
