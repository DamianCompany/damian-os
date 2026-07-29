<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Pages;

use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Jobs\SincronizarArchivosSolicitudInvestiga;
use App\Models\SolicitudInvestiga;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CrearSolicitudInvestiga extends CreateRecord
{
    protected static string $resource = SolicitudInvestigaResource::class;

    protected static ?string $title = 'Nueva idea o solicitud';

    /**
     * @var list<string>
     */
    private array $archivosTemporales = [];

    public function getSubheading(): ?string
    {
        return 'Registra lo esencial. La definición técnica se realizará en la siguiente etapa.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->archivosTemporales = array_values(array_filter($data['archivos_temporales'] ?? []));
        unset($data['archivos_temporales']);

        $data['estado'] = 'idea_registrada';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var SolicitudInvestiga $solicitud */
        $solicitud = $this->record;
        $disk = Storage::disk('local');

        foreach ($this->archivosTemporales as $ruta) {
            if (! $disk->exists($ruta)) {
                continue;
            }

            $solicitud->archivos()->firstOrCreate(
                ['ruta_temporal' => $ruta],
                [
                    'tipo' => 'adjunto',
                    'nombre_original' => basename($ruta),
                    'tipo_mime' => $disk->mimeType($ruta),
                    'tamano_bytes' => $disk->size($ruta),
                ],
            );
        }

        SincronizarArchivosSolicitudInvestiga::dispatchAfterResponse($solicitud->getKey());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
