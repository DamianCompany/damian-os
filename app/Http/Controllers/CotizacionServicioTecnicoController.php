<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicioTecnico;
use App\Services\CotizacionServicioTecnicoPdf;
use Symfony\Component\HttpFoundation\Response;

class CotizacionServicioTecnicoController extends Controller
{
    public function __invoke(
        OrdenServicioTecnico $orden,
        CotizacionServicioTecnicoPdf $cotizacion,
    ): Response {
        abort_unless(in_array(auth()->user()?->role, ['gerencia', 'servicio_tecnico'], true), 403);
        abort_unless((float) $orden->precio_cotizado > 0, 404);

        return response($cotizacion->generar($orden), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$cotizacion->nombreArchivo($orden).'"',
        ]);
    }
}
