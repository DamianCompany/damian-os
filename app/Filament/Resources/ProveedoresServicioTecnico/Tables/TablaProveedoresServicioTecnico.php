<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Tables;

use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use App\Filament\Resources\ProveedoresServicioTecnico\Schemas\FormularioProveedorServicioTecnico;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TablaProveedoresServicioTecnico
{
    public static function configurar(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'damian-supplier-table damian-technical-supplier-table'])
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('razon_social')
                    ->label('Proveedor')
                    ->description(fn ($record) => $record?->nombre_comercial ?: $record?->numero_documento)
                    ->searchable(['razon_social', 'nombre_comercial', 'numero_documento', 'whatsapp'])
                    ->wrap(),
                TextColumn::make('categorias.nombre')->label('Categorías')->badge()->limitList(2)->expandableLimitedList(),
                TextColumn::make('marcas.nombre')->label('Marcas')->badge()->limitList(2)->expandableLimitedList(),
                TextColumn::make('whatsapp')->label('Contacto')->icon('heroicon-o-chat-bubble-left-right')->placeholder('Sin WhatsApp'),
                TextColumn::make('entrega_promedio_dias')
                    ->label('Entrega')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state.' días' : 'Por consultar'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => FormularioProveedorServicioTecnico::estados()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'activo' => 'success',
                        'evaluacion' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')->options(FormularioProveedorServicioTecnico::estados()),
                SelectFilter::make('categorias')->relationship('categorias', 'nombre')->multiple()->preload(),
                SelectFilter::make('marcas')->relationship('marcas', 'nombre')->multiple()->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => ProveedorServicioTecnicoResource::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Ver ficha')])
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->emptyStateHeading('Aún no hay proveedores')
            ->emptyStateDescription('Registra proveedores de equipos, herramientas y repuestos.');
    }
}
