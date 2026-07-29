<?php

namespace App\Filament\Resources\DamiOrders\Tables;

use App\Filament\Resources\DamiOrders\Actions\DamiOrderWorkflowActions;
use App\Filament\Resources\DamiOrders\DamiOrderResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DamiOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Orden')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('client_name')->label('Cliente')->searchable()->description(fn ($record):?string  => filled($record->client_document) ? $record->client_document: null),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(42)
                    ->tooltip(fn ($record): string => $record->description),
                TextColumn::make('quantity')->label('Cant.')->alignCenter(),
                TextColumn::make('filament_type')->label('Material')->badge(),
                TextColumn::make('total_price')->label('Total')->money('PEN')->sortable(),
                TextColumn::make('profit')->label('Ganancia')->money('PEN')->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('delivery_date')->label('Entrega')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => match ($state) {
                    'pending' => 'Pendiente',
                    'in_progress' => 'En proceso',
                    'completed' => 'Completado',
                    default => $state,
                })->color(fn ($state) => match ($state) {
                    'pending' => 'info',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'pending' => 'Pendiente',
                    'in_progress' => 'En proceso',
                    'completed' => 'Completado',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record): string => DamiOrderResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Ver ficha')
                    ->icon('heroicon-o-eye'),
                DamiOrderWorkflowActions::start(),
                DamiOrderWorkflowActions::updateProduction(),
                DamiOrderWorkflowActions::complete(),
                EditAction::make()
                    ->label('Corregir registro')
                    ->visible(fn ($record): bool => auth()->user()?->role === 'dami_3d' && $record->status === 'pending'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => auth()->user()?->role === 'dami_3d'),
            ]);
    }
}
