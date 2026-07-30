<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleDriveService
{
    private const API_URL = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3';

    private const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    public function verifyConnection(): array
    {
        return $this->client()
            ->get(self::API_URL.'/drives/'.config('services.google_drive.shared_drive_id'))
            ->throw()
            ->json();
    }

    public function ensureOrderFolder(string $orderNumber): array
    {
        $orderFolder = $this->findOrCreateFolder(
            $orderNumber,
            config('services.google_drive.root_folder_id'),
        );

        return [
            'order' => $orderFolder,
            'photos' => $this->findOrCreateFolder('FOTOS', $orderFolder),
            'received' => $this->findOrCreateFolder('ARCHIVOS STL', $orderFolder),
        ];
    }

    public function ensureInvestigaSolicitudFolder(string $codigo): array
    {
        $solicitudFolder = $this->findOrCreateFolder(
            $codigo,
            config('services.google_drive.investiga_root_folder_id'),
        );

        return [
            'solicitud' => $solicitudFolder,
            'adjuntos' => $this->findOrCreateFolder('01 SOLICITUD', $solicitudFolder),
            'planificacion' => $this->findOrCreateFolder('02 PLANIFICACION', $solicitudFolder),
            'datos' => $this->findOrCreateFolder('03 DATOS', $solicitudFolder),
            'experimentos' => $this->findOrCreateFolder('04 EXPERIMENTOS', $solicitudFolder),
            'resultados' => $this->findOrCreateFolder('05 RESULTADOS', $solicitudFolder),
        ];
    }

    /**
     * Crea únicamente la carpeta inicial para no bloquear el registro.
     *
     * @return array{solicitud: string, adjuntos: string}
     */
    public function ensureInvestigaInitialFolder(string $codigo): array
    {
        $solicitudFolder = $this->findOrCreateFolder(
            $codigo,
            config('services.google_drive.investiga_root_folder_id'),
        );

        return [
            'solicitud' => $solicitudFolder,
            'adjuntos' => $this->findOrCreateFolder('01 SOLICITUD', $solicitudFolder),
        ];
    }

    public function ensureInvestigaStageFolder(string $solicitudFolderId, string $etapa): string
    {
        $nombre = match ($etapa) {
            'solicitud' => '01 SOLICITUD',
            'planificacion' => '02 PLANIFICACION',
            'datos' => '03 DATOS',
            'experimentos' => '04 EXPERIMENTOS',
            'resultados' => '05 RESULTADOS',
            'cierre' => '05 RESULTADOS',
            default => throw new RuntimeException("Etapa de InvestigaLab no válida: {$etapa}"),
        };

        return $this->findOrCreateFolder($nombre, $solicitudFolderId);
    }

    /**
     * @return array{solicitud: string, adjuntos: string}
     */
    public function ensureAutomationInitialFolder(string $codigo): array
    {
        $solicitudFolder = $this->findOrCreateFolder(
            $codigo,
            config('services.google_drive.automation_root_folder_id'),
        );

        return [
            'solicitud' => $solicitudFolder,
            'adjuntos' => $this->findOrCreateFolder('01 SOLICITUD', $solicitudFolder),
        ];
    }

    public function ensureAutomationStageFolder(string $solicitudFolderId, string $etapa): string
    {
        $nombre = match ($etapa) {
            'solicitud' => '01 SOLICITUD',
            'alcance' => '02 ALCANCE',
            'cotizacion' => '03 COTIZACION',
            'proyecto' => '04 PROYECTO',
            'pruebas' => '05 PRUEBAS',
            'entrega' => '06 ENTREGA Y SOPORTE',
            default => throw new RuntimeException("Etapa de Automation no válida: {$etapa}"),
        };

        return $this->findOrCreateFolder($nombre, $solicitudFolderId);
    }

    public function upload(string $absolutePath, string $folderId, ?string $name = null): array
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("No existe el archivo local: {$absolutePath}");
        }

        $name ??= basename($absolutePath);
        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

        $this->deleteEmptyRetries($name, $folderId);

        $metadata = $this->client()
            ->post(self::API_URL.'/files?supportsAllDrives=true', [
                'name' => $name,
                'parents' => [$folderId],
            ])
            ->throw()
            ->json();

        try {
            $this->client()
                ->withBody(file_get_contents($absolutePath), $mimeType)
                ->patch(self::UPLOAD_URL.'/files/'.$metadata['id'].'?uploadType=media&supportsAllDrives=true')
                ->throw();
        } catch (\Throwable $exception) {
            $this->client()
                ->delete(self::API_URL.'/files/'.$metadata['id'].'?supportsAllDrives=true');

            throw $exception;
        }

        return $this->client()
            ->get(self::API_URL.'/files/'.$metadata['id'], [
                'fields' => 'id,name,mimeType,webViewLink,webContentLink',
                'supportsAllDrives' => 'true',
            ])
            ->throw()
            ->json();
    }

    private function deleteEmptyRetries(string $name, string $folderId): void
    {
        $escapedName = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);
        $query = sprintf(
            "name = '%s' and '%s' in parents and trashed = false",
            $escapedName,
            $folderId,
        );

        $files = $this->client()
            ->get(self::API_URL.'/files', [
                'q' => $query,
                'corpora' => 'drive',
                'driveId' => config('services.google_drive.shared_drive_id'),
                'includeItemsFromAllDrives' => 'true',
                'supportsAllDrives' => 'true',
                'fields' => 'files(id,size)',
            ])
            ->throw()
            ->json('files', []);

        foreach ($files as $file) {
            if ((int) ($file['size'] ?? 0) !== 0) {
                continue;
            }

            $this->client()
                ->delete(self::API_URL.'/files/'.$file['id'].'?supportsAllDrives=true')
                ->throw();
        }
    }

    private function findOrCreateFolder(string $name, string $parentId): string
    {
        $escapedName = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);
        $query = sprintf(
            "name = '%s' and '%s' in parents and mimeType = '%s' and trashed = false",
            $escapedName,
            $parentId,
            self::FOLDER_MIME_TYPE,
        );

        $existing = $this->folderClient()
            ->get(self::API_URL.'/files', [
                'q' => $query,
                'corpora' => 'drive',
                'driveId' => config('services.google_drive.shared_drive_id'),
                'includeItemsFromAllDrives' => 'true',
                'supportsAllDrives' => 'true',
                'fields' => 'files(id,name)',
                'pageSize' => 1,
            ])
            ->throw()
            ->json('files.0.id');

        if ($existing) {
            return $existing;
        }

        return $this->folderClient()
            ->post(self::API_URL.'/files?supportsAllDrives=true', [
                'name' => $name,
                'mimeType' => self::FOLDER_MIME_TYPE,
                'parents' => [$parentId],
            ])
            ->throw()
            ->json('id');
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout(45)
            ->retry(2, 500);
    }

    /**
     * Las operaciones de carpetas forman parte de pantallas interactivas.
     * Deben fallar rápido para que una demora de Drive no provoque un error 500.
     */
    private function folderClient(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->accessToken())
            ->connectTimeout(3)
            ->timeout(5);
    }

    private function accessToken(): string
    {
        return Cache::remember('google-drive.access-token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(6)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google_drive.client_id'),
                    'client_secret' => config('services.google_drive.client_secret'),
                    'refresh_token' => config('services.google_drive.refresh_token'),
                    'grant_type' => 'refresh_token',
                ])
                ->throw()
                ->json();

            $token = $response['access_token'] ?? null;

            if (! is_string($token) || Str::length($token) < 10) {
                throw new RuntimeException('Google no devolvió un token de acceso válido.');
            }

            return $token;
        });
    }
}
