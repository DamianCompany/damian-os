<?php

namespace App\Jobs;

use App\Models\ProveedorDami3d;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SincronizarDocumentosProveedorDami3d implements ShouldQueue
{
    use Queueable;
    public int $tries=3; public int $timeout=120; public array $backoff=[30,120,300];
    public function __construct(public int $proveedorId) {}
    public function handle(GoogleDriveService $drive): void
    {
        $proveedor=ProveedorDami3d::with('documentos')->findOrFail($this->proveedorId);
        $carpetas=$drive->ensureProveedorDami3dFolder($proveedor->codigo);
        $proveedor->updateQuietly(['carpeta_drive_id'=>$carpetas['proveedor'],'carpeta_drive_url'=>"https://drive.google.com/drive/folders/{$carpetas['proveedor']}"]);
        $disk=Storage::disk('local');
        foreach($proveedor->documentos->whereNull('archivo_drive_id') as $documento){
            if(!$documento->ruta_temporal || !$disk->exists($documento->ruta_temporal)) continue;
            $ruta=$documento->ruta_temporal;
            $archivo=$drive->upload($disk->path($ruta),$carpetas['documentos'],$documento->nombre_original);
            $documento->update(['archivo_drive_id'=>$archivo['id'],'archivo_drive_url'=>$archivo['webViewLink']??"https://drive.google.com/open?id={$archivo['id']}",'ruta_temporal'=>null]);
            $disk->delete($ruta);
        }
    }
    public function failed(?Throwable $exception):void { report($exception); }
}
