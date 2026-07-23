<?php

namespace App\Filament\Resources\DamiOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('client_name')->label('Cliente')->searchable()->description(fn ($record) => $record->client_document),
                TextColumn::make('quantity')->label('Cant.')->alignCenter(),
                TextColumn::make('filament_type')->label('Material')->badge(),
                TextColumn::make('total_price')->label('Total')->money('PEN')->sortable(),
                TextColumn::make('profit')->label('Ganancia')->money('PEN')->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('delivery_date')->label('Entrega')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => match ($state) {
                    'new', 'draft' => 'Nuevo',
                    'planned' => 'Planificado',
                    'in_progress' => 'En proceso',
                    'review' => 'Revisión',
                    'ready' => 'Terminado',
                    'blocked' => 'Bloqueado',
                    'delivered' => 'Entregado',
                    default => $state,
                }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'new' => 'Nuevo',
                    'planned' => 'Planificado',
                    'in_progress' => 'En proceso',
                    'review' => 'Revisión',
                    'ready' => 'Terminado',
                    'blocked' => 'Bloqueado',
                    'delivered' => 'Entregado',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()->visible(fn (): bool => auth()->user()?->role === 'dami_3d'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => auth()->user()?->role === 'dami_3d'),
            ]);
    }
}
