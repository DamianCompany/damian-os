<?php

namespace App\Filament\Resources\SolicitudesAutomation\Pages;

use App\Filament\Resources\SolicitudesAutomation\Schemas\FormularioProyectoAutomation;
use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Throwable;

class GestionarProyectoAutomation extends EditRecord
{
    protected static string $resource = SolicitudAutomationResource::class;

    protected static ?string $title = 'Gestionar proyecto de Automation';

    public function form(Schema $schema): Schema
    {
        return FormularioProyectoAutomation::configurar($schema);
    }

    public function getSubheading(): ?string
    {
        return 'Completa alcance, cotización, ejecución, pruebas y entrega de manera progresiva.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')->label('Volver a la ficha')->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['etapa_actual'] = $this->calcularEtapa($data);
        $data['avance'] = $this->calcularAvance($data);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->carpeta_drive_id) {
            return;
        }

        $etapa = $this->record->etapa_actual;
        $carpetas = $this->record->carpetas_drive ?? [];
        if (isset($carpetas[$etapa])) {
            return;
        }

        try {
            $carpetas[$etapa] = app(GoogleDriveService::class)
                ->ensureAutomationStageFolder($this->record->carpeta_drive_id, $etapa);
            $this->record->updateQuietly(['carpetas_drive' => $carpetas]);
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->warning()->title('Proyecto guardado')
                ->body('La carpeta de esta etapa quedó pendiente de sincronizar con Drive.')->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    private function calcularEtapa(array $data): string
    {
        if (in_array($data['estado'] ?? null, ['entregado', 'en_soporte', 'cerrado'], true)) {
            return 'entrega';
        }
        if (! empty($data['pruebas']) || ! empty($data['fecha_instalacion'])) {
            return 'pruebas';
        }
        if (! empty($data['tareas']) || ! empty($data['fecha_aprobacion'])) {
            return 'proyecto';
        }
        if (! empty($data['costo_estimado']) || ! empty($data['actividades'])) {
            return 'cotizacion';
        }
        if (! empty($data['objetivo']) || ! empty($data['alcance_incluido'])) {
            return 'alcance';
        }

        return 'solicitud';
    }

    private function calcularAvance(array $data): int
    {
        $bloques = [
            ! empty($data['cliente']) && ! empty($data['necesidad']),
            ! empty($data['objetivo']) && ! empty($data['criterios_aceptacion']),
            ! empty($data['factibilidad']) && ! empty($data['precio_venta']),
            ! empty($data['fecha_aprobacion']) && ! empty($data['tareas']),
            ! empty($data['materiales']) || ! empty($data['servicios_externos']),
            ! empty($data['pruebas']),
            ! empty($data['fecha_entrega']),
        ];

        return (int) round((count(array_filter($bloques)) / count($bloques)) * 100);
    }
}
