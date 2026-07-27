<?php

namespace App\Console\Commands;

use App\Models\DamiOrderFile;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateLocalOrderFilesToGoogleDrive extends Command
{
    protected $signature = 'drive:migrate-local-files {--dry-run : Solo muestra los archivos pendientes}';

    protected $description = 'Copia a Google Drive los archivos locales de pedidos todavía no sincronizados';

    public function handle(GoogleDriveService $drive): int
    {
        $files = DamiOrderFile::query()
            ->with('order:id,order_number')
            ->whereNull('google_drive_file_id')
            ->orderBy('id')
            ->get();

        if ($files->isEmpty()) {
            $this->info('No hay archivos pendientes de migración.');

            return self::SUCCESS;
        }

        $this->info("Archivos pendientes: {$files->count()}");

        if ($this->option('dry-run')) {
            $files->each(fn (DamiOrderFile $file) => $this->line(
                "#{$file->id} · {$file->order?->order_number} · {$file->type} · {$file->path}",
            ));

            return self::SUCCESS;
        }

        try {
            $sharedDrive = $drive->verifyConnection();
            $this->info('Unidad compartida conectada: '.($sharedDrive['name'] ?? 'Google Drive'));
        } catch (Throwable $exception) {
            $this->error('No se pudo conectar con la Unidad compartida: '.$exception->getMessage());

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($files as $file) {
            if (! $file->order || ! Storage::disk('local')->exists($file->path)) {
                $this->warn("#{$file->id}: archivo local o pedido no encontrado; se omitió.");
                $failed++;
                continue;
            }

            try {
                $folders = $drive->ensureOrderFolder($file->order->order_number);
                $folderId = $file->type === 'reference'
                    ? $folders['photos']
                    : $folders['received'];
                $uploaded = $drive->upload(
                    Storage::disk('local')->path($file->path),
                    $folderId,
                    basename($file->path),
                );

                $file->update([
                    'google_drive_file_id' => $uploaded['id'],
                    'google_drive_url' => $uploaded['webViewLink']
                        ?? "https://drive.google.com/open?id={$uploaded['id']}",
                    'google_drive_folder_id' => $folderId,
                    'synced_to_drive_at' => now(),
                ]);

                $this->info("#{$file->id}: {$file->path} subido correctamente.");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("#{$file->id}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info('Migración terminada. Los originales locales se conservaron.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
