<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Pages;

use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Size;

class VerOrdenServicioTecnico extends ViewRecord
{
    protected static string $resource = OrdenServicioTecnicoResource::class;
    protected string $view = 'filament.resources.ordenes-servicio-tecnico.pages.ver-orden-servicio-tecnico';

    public function getTitle(): string
    {
        return "Orden {$this->getRecord()->codigo}";
    }

    public function getSubheading(): ?string
    {
        $trabajo = $this->getRecord()->tipo_atencion === 'mantenimiento' ? 'mantenimiento' : 'reparación';

        return "Recepción, diagnóstico, {$trabajo}, prueba y entrega";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('iniciar_diagnostico')
                ->label('Iniciar diagnóstico')->icon('heroicon-o-magnifying-glass')->color('info')
                ->visible(fn (): bool => $this->puedeOperar() && $this->record->estado === 'ingresado')
                ->requiresConfirmation()
                ->action(fn () => $this->cambiarEstado('en_diagnostico', 'Diagnóstico', 'Diagnóstico iniciado')),
            Action::make('iniciar_reparacion')
                ->label(fn (): string => $this->record->tipo_atencion === 'mantenimiento'
                    ? 'Iniciar mantenimiento'
                    : 'Iniciar reparación')
                ->icon('heroicon-o-play')->color('success')
                ->visible(fn (): bool => $this->puedeOperar()
                    && $this->record->decision_cliente === 'aprobada'
                    && in_array($this->record->estado, ['esperando_aprobacion', 'esperando_repuesto'], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $trabajo = $this->record->tipo_atencion === 'mantenimiento' ? 'Mantenimiento' : 'Reparación';
                    $this->record->update([
                        'estado' => 'en_reparacion',
                        'etapa_actual' => 'reparacion',
                        'ubicacion_fisica' => $trabajo,
                        'trabajo_iniciado_en' => now(),
                    ]);
                    $this->confirmar("{$trabajo} iniciado");
                }),
            Action::make('finalizar_reparacion')
                ->label('Enviar a pruebas')->icon('heroicon-o-clipboard-document-check')->color('info')
                ->visible(fn (): bool => $this->puedeOperar() && $this->record->estado === 'en_reparacion')
                ->requiresConfirmation()
                ->action(function (): void {
                    $inicio = $this->record->trabajo_iniciado_en;
                    $this->record->update([
                        'estado' => 'en_pruebas',
                        'etapa_actual' => 'entrega',
                        'ubicacion_fisica' => 'Pruebas',
                        'trabajo_finalizado_en' => now(),
                        'tiempo_real_minutos' => $inicio ? $inicio->diffInMinutes(now()) : null,
                    ]);
                    $this->confirmar('Equipo enviado a pruebas');
                }),
            Action::make('gestionar')
                ->label('Completar información')->icon('heroicon-o-pencil-square')->color('success')->size(Size::Large)
                ->visible(fn (): bool => $this->puedeOperar())
                ->url(static::getResource()::getUrl('edit', ['record' => $this->record])),
            Action::make('descargar_cotizacion')
                ->label('Descargar cotización PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => (float) $this->record->precio_cotizado > 0)
                ->url(fn (): string => route('servicio-tecnico.cotizacion.pdf', $this->record)),
            Action::make('regresar')
                ->label('Regresar al listado')->icon('heroicon-o-arrow-left')->size(Size::Large)
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    private function puedeOperar(): bool
    {
        return static::getResource()::canEdit($this->record);
    }

    private function cambiarEstado(string $estado, string $ubicacion, string $mensaje): void
    {
        $this->record->update(['estado' => $estado, 'ubicacion_fisica' => $ubicacion]);
        $this->confirmar($mensaje);
    }

    private function confirmar(string $mensaje): void
    {
        Notification::make()->success()->title($mensaje)->send();
        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }
}
