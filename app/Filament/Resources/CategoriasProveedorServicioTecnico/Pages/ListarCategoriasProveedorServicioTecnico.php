<?php

namespace App\Filament\Resources\CategoriasProveedorServicioTecnico\Pages;

use App\Filament\Resources\CategoriasProveedorServicioTecnico\CategoriaProveedorServicioTecnicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarCategoriasProveedorServicioTecnico extends ListRecords
{
    protected static string $resource = CategoriaProveedorServicioTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva categoría')->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return 'Clasificación de equipos, herramientas, insumos y repuestos.';
    }
}
