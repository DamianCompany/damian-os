<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Pages;

use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use App\Jobs\SincronizarArchivosServicioTecnico;
use App\Models\OrdenServicioTecnico;
use App\Models\MarcaProveedorServicioTecnico;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CrearOrdenServicioTecnico extends CreateRecord
{
    protected static string $resource = OrdenServicioTecnicoResource::class;
    protected static ?string $title = 'Nuevo ingreso';
    private array $archivosTemporales = [];

    public function getSubheading(): ?string
    {
        return 'Identifica al cliente, el equipo y la falla. El diagnóstico se realiza después.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->archivosTemporales = array_values(array_filter($data['archivos_temporales'] ?? []));
        unset($data['archivos_temporales']);
        $data['estado'] = 'ingresado';
        $data['marca'] = filled($data['marca_servicio_tecnico_id'] ?? null)
            ? MarcaProveedorServicioTecnico::find($data['marca_servicio_tecnico_id'])?->nombre
            : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var OrdenServicioTecnico $orden */
        $orden = $this->record;
        $disk = Storage::disk('local');
        foreach ($this->archivosTemporales as $ruta) {
            if (! $disk->exists($ruta)) {
                continue;
            }
            $orden->archivos()->create([
                'ruta_temporal' => $ruta,
                'nombre_original' => basename($ruta),
                'tipo_mime' => $disk->mimeType($ruta),
                'tamano_bytes' => $disk->size($ruta),
            ]);
        }

        if (filled(config('services.google_drive.servicio_tecnico_root_folder_id'))) {
            SincronizarArchivosServicioTecnico::dispatchAfterResponse($orden->id);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
