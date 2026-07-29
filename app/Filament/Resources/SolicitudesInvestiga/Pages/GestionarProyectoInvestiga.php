<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Pages;

use App\Filament\Resources\SolicitudesInvestiga\Schemas\FormularioProyectoInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Throwable;

class GestionarProyectoInvestiga extends EditRecord
{
    protected static string $resource = SolicitudInvestigaResource::class;

    protected static ?string $title = 'Gestionar proyecto';

    public function form(Schema $schema): Schema
    {
        return FormularioProyectoInvestiga::configurar($schema);
    }

    public function getSubheading(): ?string
    {
        return 'Completa cada etapa progresivamente. Puedes guardar y continuar después.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver a la ficha')
                ->icon('heroicon-o-arrow-left')
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

        $etapa = match ($this->record->etapa_actual) {
            'definicion' => 'solicitud',
            'cierre' => 'resultados',
            default => $this->record->etapa_actual,
        };
        $carpetas = $this->record->carpetas_drive ?? [];

        if (isset($carpetas[$etapa])) {
            return;
        }

        try {
            $carpetas[$etapa] = app(GoogleDriveService::class)
                ->ensureInvestigaStageFolder($this->record->carpeta_drive_id, $etapa);

            $this->record->updateQuietly(['carpetas_drive' => $carpetas]);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->warning()
                ->title('Proyecto guardado')
                ->body('La carpeta de la etapa quedó pendiente de sincronizar con Drive.')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    private function calcularEtapa(array $data): string
    {
        if (($data['estado'] ?? null) === 'cerrado') {
            return 'cierre';
        }

        if (! empty($data['resultado_principal']) || ! empty($data['entregables'])) {
            return 'resultados';
        }

        if (! empty($data['experimentos'])) {
            return 'experimentos';
        }

        if (! empty($data['datasets'])) {
            return 'datos';
        }

        if (! empty($data['factibilidad']) || ! empty($data['actividades'])) {
            return 'planificacion';
        }

        return ! empty($data['objetivo_general']) ? 'definicion' : 'solicitud';
    }

    private function calcularAvance(array $data): int
    {
        $bloques = [
            ! empty($data['titulo']) && ! empty($data['problema_necesidad']),
            ! empty($data['objetivo_general']) && ! empty($data['criterios_exito']),
            ! empty($data['factibilidad']) && ! empty($data['actividades']),
            ! empty($data['datasets']),
            ! empty($data['experimentos']),
            ! empty($data['resultado_principal']) && ! empty($data['entregables']),
        ];

        return (int) round((count(array_filter($bloques)) / count($bloques)) * 100);
    }
}
