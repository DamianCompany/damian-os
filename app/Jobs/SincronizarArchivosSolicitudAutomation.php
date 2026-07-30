<?php

namespace App\Jobs;

use App\Models\SolicitudAutomation;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SincronizarArchivosSolicitudAutomation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $solicitudId) {}

    public function handle(GoogleDriveService $drive): void
    {
        $solicitud = SolicitudAutomation::query()->with('archivos')->findOrFail($this->solicitudId);
        $carpetas = $drive->ensureAutomationInitialFolder($solicitud->codigo);

        $solicitud->updateQuietly([
            'carpeta_drive_id' => $carpetas['solicitud'],
            'carpeta_drive_url' => "https://drive.google.com/drive/folders/{$carpetas['solicitud']}",
            'carpetas_drive' => ['solicitud' => $carpetas['adjuntos']],
        ]);

        $disk = Storage::disk('local');

        foreach ($solicitud->archivos->whereNull('sincronizado_drive_en') as $pendiente) {
            if (! is_string($pendiente->ruta_temporal) || ! $disk->exists($pendiente->ruta_temporal)) {
                continue;
            }

            $archivo = $drive->upload(
                $disk->path($pendiente->ruta_temporal),
                $carpetas['adjuntos'],
                $pendiente->nombre_original,
            );

            $pendiente->update([
                'archivo_drive_id' => $archivo['id'],
                'archivo_drive_url' => $archivo['webViewLink'] ?? "https://drive.google.com/open?id={$archivo['id']}",
                'sincronizado_drive_en' => now(),
            ]);
            $disk->delete($pendiente->ruta_temporal);
        }
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
