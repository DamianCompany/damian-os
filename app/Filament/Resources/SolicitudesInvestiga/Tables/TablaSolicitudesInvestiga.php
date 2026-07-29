<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Tables;

use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Filament\Resources\SolicitudesInvestiga\Schemas\FormularioProyectoInvestiga;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TablaSolicitudesInvestiga
{
    public static function configurar(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('titulo')
                    ->label('Idea o solicitud')
                    ->description(fn ($record): string => $record->solicitante)
                    ->searchable(['titulo', 'solicitante', 'problema_necesidad'])
                    ->limit(48),
                TextColumn::make('tipo_proyecto')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::etiquetaTipo($state)),
                TextColumn::make('avance')
                    ->label('Avance')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('fecha_requerida')
                    ->label('Fecha requerida')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::etiquetaEstado($state))
                    ->color(fn (string $state): string => match ($state) {
                        'idea_registrada', 'en_definicion' => 'info',
                        'en_evaluacion', 'en_analisis' => 'warning',
                        'proyecto_activo', 'en_ejecucion', 'listo_entrega', 'entregado' => 'success',
                        'bloqueado' => 'danger',
                        'en_validacion', 'en_documentacion' => 'primary',
                        'cerrado' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(FormularioProyectoInvestiga::estados()),
                SelectFilter::make('tipo_proyecto')
                    ->label('Tipo de proyecto')
                    ->options([
                        'investigacion' => 'Investigación',
                        'innovacion' => 'Innovación',
                        'prototipo' => 'Prototipo',
                        'inteligencia_artificial' => 'Inteligencia artificial',
                        'analisis_datos' => 'Análisis de datos',
                        'capacitacion' => 'Capacitación',
                        'otro' => 'Otro',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record): string => SolicitudInvestigaResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Ver ficha')
                    ->icon('heroicon-o-eye'),
            ]);
    }

    public static function etiquetaTipo(string $tipo): string
    {
        return match ($tipo) {
            'investigacion' => 'Investigación',
            'innovacion' => 'Innovación',
            'prototipo' => 'Prototipo',
            'inteligencia_artificial' => 'Inteligencia artificial',
            'analisis_datos' => 'Análisis de datos',
            'capacitacion' => 'Capacitación',
            default => 'Otro',
        };
    }

    public static function etiquetaEstado(string $estado): string
    {
        return FormularioProyectoInvestiga::estados()[$estado] ?? $estado;
    }
}
