<?php

namespace App\Filament\Resources\Printers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrintersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Impresora')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-printer'),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('status')
                    ->label('Disponibilidad')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'available' => 'Disponible',
                        'in_use' => 'En uso',
                        'maintenance' => 'En mantenimiento',
                        'out_of_service' => 'Fuera de servicio',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'available' => 'success',
                        'in_use' => 'info',
                        'maintenance' => 'warning',
                        'out_of_service' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label('Registro')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Disponibilidad')
                    ->options([
                        'available' => 'Disponible',
                        'in_use' => 'En uso',
                        'maintenance' => 'En mantenimiento',
                        'out_of_service' => 'Fuera de servicio',
                    ]),
            ])
            ->emptyStateIcon('heroicon-o-printer')
            ->emptyStateHeading('Aún no hay impresoras')
            ->emptyStateDescription('Agrega la primera indicando solo su nombre y ubicación.')
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => auth()->user()?->role === 'gerencia'),
            ]);
    }
}
