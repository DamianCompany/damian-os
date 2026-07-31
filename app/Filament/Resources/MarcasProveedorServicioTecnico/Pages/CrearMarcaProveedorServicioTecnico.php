<?php

namespace App\Filament\Resources\MarcasProveedorServicioTecnico\Pages;

use App\Filament\Resources\MarcasProveedorServicioTecnico\MarcaProveedorServicioTecnicoResource;
use Filament\Resources\Pages\CreateRecord;

class CrearMarcaProveedorServicioTecnico extends CreateRecord
{
    protected static string $resource = MarcaProveedorServicioTecnicoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl();
    }
}
