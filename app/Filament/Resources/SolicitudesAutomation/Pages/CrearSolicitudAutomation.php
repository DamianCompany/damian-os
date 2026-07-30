<?php

namespace App\Filament\Resources\SolicitudesAutomation\Pages;

use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use App\Jobs\SincronizarArchivosSolicitudAutomation;
use App\Models\SolicitudAutomation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CrearSolicitudAutomation extends CreateRecord
{
    protected static string $resource = SolicitudAutomationResource::class;

    protected static ?string $title = 'Nueva solicitud de Automation';

    private array $archivosTemporales = [];

    public function getSubheading(): ?string
    {
        return 'Registra la necesidad. El alcance técnico y la cotización se completarán después.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->archivosTemporales = array_values(array_filter($data['archivos_temporales'] ?? []));
        unset($data['archivos_temporales']);
        $data['estado'] = 'solicitud';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var SolicitudAutomation $solicitud */
        $solicitud = $this->record;
        $disk = Storage::disk('local');

        foreach ($this->archivosTemporales as $ruta) {
            if (! $disk->exists($ruta)) {
                continue;
            }
            $solicitud->archivos()->create([
                'ruta_temporal' => $ruta,
                'nombre_original' => basename($ruta),
                'tipo_mime' => $disk->mimeType($ruta),
                'tamano_bytes' => $disk->size($ruta),
            ]);
        }

        if (filled(config('services.google_drive.automation_root_folder_id'))) {
            SincronizarArchivosSolicitudAutomation::dispatchAfterResponse($solicitud->getKey());
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
