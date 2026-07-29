<?php

namespace App\Jobs;

use App\Models\SolicitudInvestiga;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SincronizarArchivosSolicitudInvestiga implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $solicitudId) {}

    public function handle(GoogleDriveService $drive): void
    {
        $solicitud = SolicitudInvestiga::query()
            ->with('archivos')
            ->findOrFail($this->solicitudId);

        $carpetas = $drive->ensureInvestigaInitialFolder($solicitud->codigo);

        $solicitud->update([
            'carpeta_drive_id' => $carpetas['solicitud'],
            'carpeta_drive_url' => "https://drive.google.com/drive/folders/{$carpetas['solicitud']}",
            'carpetas_drive' => ['solicitud' => $carpetas['adjuntos']],
        ]);

        $disk = Storage::disk('local');

        foreach ($solicitud->archivos->whereNull('sincronizado_drive_en') as $archivoPendiente) {
            $ruta = $archivoPendiente->ruta_temporal;

            if (! is_string($ruta) || ! $disk->exists($ruta)) {
                continue;
            }

            $archivoDrive = $drive->upload(
                $disk->path($ruta),
                $carpetas['adjuntos'],
                $archivoPendiente->nombre_original,
            );

            $archivoPendiente->update([
                'archivo_drive_id' => $archivoDrive['id'],
                'archivo_drive_url' => $archivoDrive['webViewLink']
                    ?? "https://drive.google.com/open?id={$archivoDrive['id']}",
                'sincronizado_drive_en' => now(),
            ]);

            $disk->delete($ruta);
        }
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
