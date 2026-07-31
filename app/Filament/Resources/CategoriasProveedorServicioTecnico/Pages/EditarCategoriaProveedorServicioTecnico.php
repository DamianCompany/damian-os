<?php

namespace App\Filament\Resources\CategoriasProveedorServicioTecnico\Pages;

use App\Filament\Resources\CategoriasProveedorServicioTecnico\CategoriaProveedorServicioTecnicoResource;
use Filament\Resources\Pages\EditRecord;

class EditarCategoriaProveedorServicioTecnico extends EditRecord
{
    protected static string $resource = CategoriaProveedorServicioTecnicoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl();
    }
}
