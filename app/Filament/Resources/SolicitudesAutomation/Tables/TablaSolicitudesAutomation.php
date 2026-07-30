<?php

namespace App\Filament\Resources\SolicitudesAutomation\Tables;

use App\Filament\Resources\SolicitudesAutomation\Schemas\FormularioSolicitudAutomation;
use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TablaSolicitudesAutomation
{
    public static function configurar(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('titulo')
                    ->label('Proyecto')
                    ->description(fn ($record): string => $record->cliente)
                    ->searchable(['titulo', 'cliente', 'necesidad'])
                    ->limit(46),
                TextColumn::make('tipo_servicio')
                    ->label('Servicio')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::etiquetaTipo($state)),
                TextColumn::make('avance')->label('Avance')->suffix('%')->sortable(),
                TextColumn::make('precio_venta')->label('Cotización')->money('PEN')->placeholder('Por calcular'),
                TextColumn::make('fecha_requerida')->label('Fecha requerida')->date('d/m/Y')->placeholder('Sin fecha')->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::etiquetaEstado($state))
                    ->color(fn (string $state): string => match ($state) {
                        'solicitud', 'en_evaluacion' => 'info',
                        'cotizacion_enviada', 'esperando_aprobacion' => 'warning',
                        'proyecto_activo', 'en_ejecucion', 'listo_entrega', 'entregado' => 'success',
                        'bloqueado' => 'danger',
                        'en_pruebas', 'en_soporte' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(self::estados()),
                SelectFilter::make('tipo_servicio')->label('Servicio')->options(FormularioSolicitudAutomation::tiposServicio()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record): string => SolicitudAutomationResource::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Ver ficha')->icon('heroicon-o-eye')]);
    }

    public static function estados(): array
    {
        return [
            'solicitud' => 'Solicitud',
            'en_evaluacion' => 'En evaluación',
            'cotizacion_enviada' => 'Cotización enviada',
            'esperando_aprobacion' => 'Esperando aprobación',
            'proyecto_activo' => 'Proyecto activo',
            'en_ejecucion' => 'En ejecución',
            'bloqueado' => 'Bloqueado',
            'en_pruebas' => 'En pruebas',
            'listo_entrega' => 'Listo para entrega',
            'entregado' => 'Entregado',
            'en_soporte' => 'En soporte',
            'cerrado' => 'Cerrado',
        ];
    }

    public static function etiquetaEstado(string $estado): string
    {
        return self::estados()[$estado] ?? $estado;
    }

    public static function etiquetaTipo(string $tipo): string
    {
        return FormularioSolicitudAutomation::tiposServicio()[$tipo] ?? 'Otro';
    }
}
