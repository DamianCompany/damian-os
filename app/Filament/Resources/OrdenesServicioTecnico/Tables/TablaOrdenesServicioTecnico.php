<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Tables;

use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use App\Filament\Resources\OrdenesServicioTecnico\Schemas\FormularioIngresoServicioTecnico;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TablaOrdenesServicioTecnico
{
    public static function configurar(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Orden')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('cliente')->label('Cliente / equipo')
                    ->description(fn ($record): string => self::etiquetaEquipo($record->tipo_equipo).' · '.trim("{$record->marca} {$record->modelo}"))
                    ->searchable(['cliente', 'telefono', 'numero_serie', 'falla_reportada']),
                TextColumn::make('falla_reportada')->label('Falla reportada')->limit(45)->wrap(),
                TextColumn::make('tipo_atencion')->label('Atención')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'mantenimiento' ? 'Mantenimiento' : 'Reparación')
                    ->color(fn (string $state): string => $state === 'mantenimiento' ? 'info' : 'success'),
                TextColumn::make('ubicacion_fisica')->label('Ubicación')->icon('heroicon-o-map-pin'),
                TextColumn::make('fecha_entrega_estimada')->label('Entrega')->date('d/m/Y')->placeholder('Por definir')->sortable(),
                TextColumn::make('precio_cotizado')->label('Cotización')->money('PEN')->placeholder('Por calcular'),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state): string => self::etiquetaEstado($state))
                    ->color(fn (string $state): string => match ($state) {
                        'ingresado', 'en_diagnostico' => 'info',
                        'esperando_aprobacion', 'esperando_repuesto' => 'warning',
                        'en_reparacion', 'en_pruebas' => 'primary',
                        'listo_entrega', 'entregado' => 'success',
                        'no_reparado' => 'danger',
                        'en_garantia' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(self::estados()),
                SelectFilter::make('tipo_equipo')->label('Equipo')->options(FormularioIngresoServicioTecnico::tiposEquipo()),
                SelectFilter::make('tipo_atencion')->label('Atención')->options([
                    'mantenimiento' => 'Mantenimiento',
                    'reparacion' => 'Reparación',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record): string => OrdenServicioTecnicoResource::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Ver orden')->icon('heroicon-o-eye')]);
    }

    public static function estados(): array
    {
        return [
            'ingresado' => 'Ingresado',
            'en_diagnostico' => 'En diagnóstico',
            'esperando_aprobacion' => 'Esperando aprobación',
            'esperando_repuesto' => 'Esperando repuesto',
            'en_reparacion' => 'En reparación',
            'en_pruebas' => 'En pruebas',
            'listo_entrega' => 'Listo para entrega',
            'no_reparado' => 'No reparado',
            'entregado' => 'Entregado',
            'en_garantia' => 'En garantía',
        ];
    }

    public static function etiquetaEstado(string $estado): string
    {
        return self::estados()[$estado] ?? $estado;
    }

    public static function etiquetaEquipo(string $tipo): string
    {
        return FormularioIngresoServicioTecnico::tiposEquipo()[$tipo] ?? 'Otro equipo';
    }
}
