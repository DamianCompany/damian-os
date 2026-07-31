<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Pages;

use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use App\Filament\Resources\OrdenesServicioTecnico\Schemas\FormularioTrabajoServicioTecnico;
use App\Services\GoogleDriveService;
use App\Services\CotizacionServicioTecnicoPdf;
use App\Models\MarcaProveedorServicioTecnico;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GestionarOrdenServicioTecnico extends EditRecord
{
    protected static string $resource = OrdenServicioTecnicoResource::class;
    protected static ?string $title = 'Gestionar orden';

    public function form(Schema $schema): Schema
    {
        return FormularioTrabajoServicioTecnico::configurar($schema);
    }

    public function getSubheading(): ?string
    {
        return 'Diagnostica, repara y entrega sin repetir los datos de recepción.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')->label('Volver a la orden')->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['marca'] = filled($data['marca_servicio_tecnico_id'] ?? null)
            ? MarcaProveedorServicioTecnico::find($data['marca_servicio_tecnico_id'])?->nombre
            : $this->record->marca;
        $data['etapa_actual'] = $this->calcularEtapa($data);
        $data['avance'] = $this->calcularAvance($data);
        $data['estado'] = $this->calcularEstado($data);
        if (($data['decision_cliente'] ?? null) === 'aprobada' && empty($data['fecha_aprobacion'])) {
            $data['fecha_aprobacion'] = now();
        }
        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->carpeta_drive_id
            && blank(config('services.google_drive.servicio_tecnico_root_folder_id'))) {
            return;
        }

        try {
            if (! $this->record->carpeta_drive_id) {
                $inicial = app(GoogleDriveService::class)->ensureServicioTecnicoInitialFolder($this->record->codigo);
                $this->record->updateQuietly([
                    'carpeta_drive_id' => $inicial['orden'],
                    'carpeta_drive_url' => "https://drive.google.com/drive/folders/{$inicial['orden']}",
                    'carpetas_drive' => ['ingreso' => $inicial['ingreso']],
                ]);
                $this->record->refresh();
            }

            $etapa = $this->record->etapa_actual;
            $carpetas = $this->record->carpetas_drive ?? [];
            if (! isset($carpetas[$etapa])) {
                $carpetas[$etapa] = app(GoogleDriveService::class)
                    ->ensureServicioTecnicoStageFolder($this->record->carpeta_drive_id, $etapa);
                $this->record->updateQuietly(['carpetas_drive' => $carpetas]);
            }
            $this->guardarCotizacionAprobada($carpetas);
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->warning()->title('Orden guardada')
                ->body('La carpeta de esta etapa quedó pendiente de sincronizar con Drive.')->send();
        }
    }

    private function guardarCotizacionAprobada(array $carpetas): void
    {
        if ($this->record->decision_cliente !== 'aprobada'
            || ! $this->record->precio_cotizado
            || $this->record->cotizacion_drive_id) {
            return;
        }

        $carpeta = $carpetas['diagnostico'] ?? app(GoogleDriveService::class)
            ->ensureServicioTecnicoStageFolder($this->record->carpeta_drive_id, 'diagnostico');
        $generador = app(CotizacionServicioTecnicoPdf::class);
        $this->record->updateQuietly(['cotizacion_generada_en' => now()]);

        $ruta = 'servicio-tecnico/cotizaciones/'.$generador->nombreArchivo($this->record);
        $disk = Storage::disk('local');
        $disk->put($ruta, $generador->generar($this->record->fresh()));

        try {
            $archivo = app(GoogleDriveService::class)->upload(
                $disk->path($ruta),
                $carpeta,
                $generador->nombreArchivo($this->record),
            );
            $this->record->updateQuietly([
                'cotizacion_drive_id' => $archivo['id'],
                'cotizacion_drive_url' => $archivo['webViewLink'] ?? "https://drive.google.com/open?id={$archivo['id']}",
            ]);
        } finally {
            $disk->delete($ruta);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    private function calcularEtapa(array $data): string
    {
        if (! empty($data['resultado_prueba']) || ! empty($data['entregado_en'])) {
            return 'entrega';
        }
        if (! empty($data['trabajo_iniciado_en']) || ! empty($data['trabajo_realizado'])) {
            return 'reparacion';
        }
        return ! empty($data['diagnostico']) ? 'diagnostico' : 'ingreso';
    }

    private function calcularAvance(array $data): int
    {
        $bloques = [
            true,
            ! empty($data['diagnostico']) && ! empty($data['resultado_tecnico']),
            ! empty($data['decision_cliente']),
            ! empty($data['trabajo_realizado']),
            ! empty($data['resultado_prueba']),
            ! empty($data['entregado_en']),
        ];
        return (int) round((count(array_filter($bloques)) / count($bloques)) * 100);
    }

    private function calcularEstado(array $data): string
    {
        if (! empty($data['entregado_en'])) {
            return 'entregado';
        }
        if (($data['resultado_prueba'] ?? null) === 'conforme') {
            return 'listo_entrega';
        }
        if (($data['resultado_prueba'] ?? null) === 'no_reparable') {
            return 'no_reparado';
        }
        if (! empty($data['trabajo_finalizado_en'])) {
            return 'en_pruebas';
        }
        if (! empty($data['trabajo_iniciado_en'])) {
            return 'en_reparacion';
        }
        if (! empty($data['precio_cotizado'])) {
            return ($data['decision_cliente'] ?? null) === 'rechazada'
                ? 'no_reparado'
                : 'esperando_aprobacion';
        }
        if (! empty($data['diagnostico'])) {
            return 'en_diagnostico';
        }

        return 'ingresado';
    }
}
